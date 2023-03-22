<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeProduct;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Models\StripeProduct;
use BlackSpot\StripeMultipleAccounts\Models\StripeSubscriptionItem;
use BlackSpot\StripeMultipleAccounts\Relationships\BelongsToServiceIntegration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeProduct extends Model
{
    use ManagesAuthCredentials;
    use BelongsToServiceIntegration;

    /**
     * The stripe product instance
     * 
     * @var \Stripe\Product|null
     */
    protected $stripeProduct = null;
    
    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stripe_products';
    public const TABLE_NAME = 'stripe_products';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;    

    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function model()
    {
        return $this->morphTo('model');   
    }

    /**
     * Get the value of property from the memo
     *
     * @param string $property
     * 
     * @return \Stripe\Product|null|string
     */
    protected function getFromMemo($property)
    {
        if (! is_null($this->{$property})) {
            return $this->{$property};
        }

        return ;
    }    

    /**
     * Get the stripe product
     *
     * @return \Stripe\Product
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeProduct
     */
    public function asStripe()
    {
        $stripeCustomer = $this->getFromMemo('stripeProduct');
        
        if (! is_null($stripeCustomer)) {
            return $stripeCustomer;
        }

        return $this->stripeProduct = $this->getStripeClientConnection($this->service_integration_id)->products->retrieve($this->product_id);
    }

    /**
     * Update stripe product
     * 
     * Local connection
     * stripe connection
     *
     * @param array $opts
     * @return $this
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeProduct
     */
    public function updateStripeProduct(array $opts = [])
    {
        $this->assertExistsAsStripe();

        $stripeProduct = $this->getStripeClientConnection($this->service_integration_id)->products->update($this->product_id, (array) $opts);

        $this->fill([
            'name'             => $stripeProduct->name,
            'default_price_id' => $stripeProduct->default_price,
            'active'           => $stripeProduct->active,
            'unit_label'       => $stripeProduct->unit_label,
            //'description'     => $stripeProduct->description,
        ])->save();

        $this->putStripeProduct($stripeProduct);

        return $this;
    }


    /**
     * Delete stripe product
     * 
     * local connection
     * stripe connection
     * 
     * The stripe product will be disabled and the local register will be deleted
     * StripePHP api not allows delete products, you must delete it from the dashboard
     *
     * @return $this
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeProduct
     */
    public function deleteStripeProduct()
    {        
        $this->assertExistsAsStripe();

        $stripeProduct = $this->getStripeClientConnection($this->service_integration_id)->products->update($this->product_id, [
            'name'   => $this->name . ' (Deleted from PHP API) ',
            'active' => false
        ]);

        $this->delete();

        $this->putStripeProduct($stripeProduct);

        return $this;
    }

    /**
     * Active the stripe product
     *
     * Local query and Stripe Api connection
     * 
     * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeProduct
     */
    public function activeStripeProduct()
    {
        return $this->updateStripeProduct(['active' => true]);
    }

    /**
     * Disable the stripe product
     *
     * Local query and Stripe Api connection
     *
     * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeProduct
     */
    public function disableStripeProduct($serviceIntegrationId)
    {
        return $this->updateStripeProduct(['active' => false]);
    }    


    /**
     * It is used for set the stripe Product that belongsTo the local model
     *
     * By default is used in the "createStripeProduct" method of the "HasStripeProducts" trait
     * 
     * @param \Stripe\Product $stripeProduct
     * 
     * @return $this
     */
    public function putStripeProduct(\Stripe\Product $stripeProduct)
    {
        $this->stripeP$stripeProduct = $stripeProduct;

        return $this;
    }

    /**
     * Assert if exists as stripe product
     *
     * @throws InvalidStripeServiceIntegration|InvalidStripeProduct
     */
    public function assertExistsAsStripe()
    {
        if (is_null($this->service_integration_id)) {
            throw InvalidStripeServiceIntegration::notYetCreated($this);
        }

        if (is_null($this->product_id)) {
            throw InvalidStripeProduct::notYetCreated($this);
        }

        $this->getStripeClientConnection($this->service_integration_id); 
    }


    public function stripe_subscription_items()
    {
        return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.subscription_item', StripeSubscriptionItem::class), 'stripe_product_id');
    }
}
