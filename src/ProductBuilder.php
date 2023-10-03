<?php 

namespace BlackSpot\StripeMultipleAccounts;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeProduct;
use BlackSpot\StripeMultipleAccounts\Models\StripeProduct;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;

class ProductBuilder
{
    use ManagesAuthCredentials;

    protected $model;

    protected $serviceIntegrationId;

    protected $name;

    protected $metadata = [];

    protected $active = true;

    protected $description = null;

    protected $images = [];

    protected $packageDimensions = [];

    protected $shippable = null;

    protected $taxCode = null;

    protected $unitLabel = null;

    protected $url = null;

    protected $defaultPriceData = [];

    /**
     * __constuct
     *
     * @param \BlackSpot\StripeMultipleAccounts\HasStripeProducts|\Illuminate\Database\Eloquent\Model  $model
     * @param int  $serviceIntegrationId
     * @param string  $name
     */
    public function __construct(EloquentModel $model, $serviceIntegrationId, $name)
    {
        $this->model                = $model;
        $this->serviceIntegrationId = $serviceIntegrationId;
        $this->name                 = $name;
    }

    /**
     * The active status to apply to new product
     *
     * @return self
     */
    public function active($active = true)
    {
        $this->active = (bool) $active;

        return $this;
    }

    /**
     * The description to apply to new product
     *
     * @param string $description
     * @return self
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * The dimensions of this product for shipping purposes to apply to new product
     * required keys = 'height','length','weight' and 'width'
     * 
     * @param array $dimensions
     * @return self
     */
    public function packageDimensions($dimensions)
    {
        $missingProperties = Collection::make(array_keys((array) $dimensions))->filter(function($property){
            return !in_array($property, ['height','length','weight','width']);
        });

        if ($missingProperties->isNotEmpty()) {            
            throw InvalidStripeProduct::missingRequiredProperties(__FUNCTION__, $missingProperties->implode(', '));
        }

        $this->packageDimensions = (array) $dimensions;

        return $this;
    }

    /**
     * Determine if the product is shippable in stripe
     *
     * @param bool $shippable
     * @return self
     */
    public function shippable($shippable = true)
    {
        $this->shippable = (bool) $shippable;

        return $this;
    }

    /**
     * A label that represents units of this product. 
     * When set, this will be included in customers’ receipts, invoices, Checkout, and the customer portal.
     *
     * @return self
     */
    public function unitLabel($label)
    {
        $this->unitLabel = $label;

        return $this;
    }

    /**
     * A URL of a publicly-accessible webpage for this product.
     *
     * @param string $url
     * @return self
     */
    public function publicUrl($url)
    {
        $this->url = $url;

        return $this;
    }

        /**
     * The metadata to apply to new product
     *
     * @param array $metadata
     * @return self
     */
    public function withMetadata($metadata)
    {
        $this->metadata = (array) $metadata;

        return $this;
    }

    /**
     * A list of up to 8 URLs of images for this product, meant to be displayable to the customer.
     *
     * @param array $images
     * @return self
     */
    public function withImages($images)
    {
        $this->images = array_slice((array) $images, 0, 8);

        return $this;
    }

    /**
     * Tax to apply to new product
     *
     * @param string $taxCodeId
     * @return self
     */
    public function withTaxCode($taxCodeId)
    {
        $this->taxCode = $taxCodeId;

        return $this;
    }

    /**
     * Data used to generate a new Price object. 
     * This Price will be set as the default price for this product.
     *
     * @param array $priceData
     * @return self
     */
    public function withDefaultPriceData($priceData)
    {
        if (! isset($priceData['currency'])) {
            throw InvalidStripeProduct::missingRequiredProperties(__FUNCTION__, 'currency');
        }

        $this->defaultPriceData = $priceData;

        return $this;
    }

    /**
     * Create the product in the local database and on Stripe
     *
     * 
     * @return \Blackspot\StripeMultipleAccounts\Models\StripeProduct
     * @throws \Exception
     */
    public function create()
    {
        $stripeClient = $this->getStripeClient($this->serviceIntegrationId);

        $product = $this->model->stripe_products()->create([
            'name'                   => $this->name,
            'current_price'          => $this->getCurrentPriceForPayload(),
            'allow_recurring'        => $this->allowsRecurringForPayload(),
            'service_integration_id' => $this->serviceIntegrationId,
            'active'                 => $this->active,
            'unit_label'             => $this->unitLabel,
            'metadata'               => [],
        ]);    

        try {
            // Creating the product in stripe
            $stripeProduct = $stripeClient->products->create($this->buildPayload($product));            
        } catch (\Exception $err) {}

        if ($stripeProduct == null) {
            $stripeProduct = null;
            $product->delete();
            return null;
        }


        // Associating the service product with the local model
        $product->update([            
            'product_id'       => $stripeProduct->id,
            'default_price_id' => $stripeProduct->default_price,
        ]);

        $product->putStripeProduct($stripeProduct);

        return $product;
    }

    /**
     * Build the payload for product creation.
     *
     * @param \Blackspot\StripeMultipleAccounts\Models\StripeProduct $product
     * @return array
     */
    protected function buildPayload($product)
    {
        $payload = array_filter([
            'name'               => $this->name,
            'description'        => $this->description,
            'default_price_data' => $this->defaultPriceData,
            'images'             => $this->images,
            'package_dimensions' => $this->packageDimensions,
            'tax_code'           => $this->taxCode,
            'unit_label'         => $this->unitLabel,
            'url'                => $this->url,
        ]);

        $payload['metadata']  = $this->getMetadataForPayload($product);
        $payload['active']    = $this->active;
        
        if ($this->shippable !== null) {
            $payload['shippable'] = $this->shippable;            
        }

        return $payload;
    }

    /**
     * Get the metadata to apply to new product
     *
     * @param \Blackspot\StripeMultipleAccounts\Models\StripeProduct  $product
     * @return void
     */
    protected function getMetadataForPayload($product)
    {
        return array_merge([
            'service_integration_id'   => $this->serviceIntegrationId,
            'service_integration_type' => ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class),
            'model_id'                 => $this->model->id,
            'model_type'               => get_class($this->model),
            'stripe_product_id'        => $product->id,
            'stripe_product_type'      => ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.product', StripeProduct::class),
        ], $this->metadata);
    }
    
    /**
     * Get the current definied unit amount
     *
     * @return int
     */
    protected function getCurrentPriceForPayload()
    {
        if (empty($this->defaultPriceData) || ! isset($this->defaultPriceData['unit_amount'])) {
            return 0;
        }
        
        return $this->defaultPriceData['unit_amount'];
    }

    /**
     * Determine if the product to be created allows recurring
     *
     * @return bool
     */
    protected function allowsRecurringForPayload()
    {
        if (empty($this->defaultPriceData)) {
            return false;
        }

        return isset($this->defaultPriceData['recurring']);
    }
}

