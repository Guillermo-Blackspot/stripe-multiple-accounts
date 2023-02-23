<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;

class StripeProduct extends Model
{
    use ManagesAuthCredentials;

    protected ?\Stripe\Product $recentlyStripeProductFetched = null;
    
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
     * Get the stripe product
     *
     * @return \Stripe\Product
     */
    public function asStripeProduct()
    {
        if ($this->recentlyStripeProductFetched instanceof \Stripe\Product) {
            return $this->recentlyStripeProductFetched;
        }

        $stripeClient = $this->getStripeClientConnection();

        if (is_null($stripeClient)) {
            return ;
        }

        return $this->recentlyStripeProductFetched = $stripeClient->products->retrieve($this->product_id);
    }

    /**
     * Set on the memory the stripe product instance
     *
     * @param \Stripe\Product $stripeProduct
     * @return \Stripe\Product
     */
    public function setAsStripeProduct(\Stripe\Product $stripeProduct)
    {
        return $this->recentlyStripeProductFetched = $stripeProduct;
    }


    /**
     * Update stripe product
     * 
     * Local query and Stripe Api connection
     *
     * @param array $opts
     * @return self
     */
    public function updateStripeProduct($opts)
    {
        $stripeProduct = $this->getStripeClientConnection()->products->update($this->product_id, (array) $opts);

        $this->fill([
            'name'             => $stripeProduct->name,
            'description'      => $stripeProduct->description,
            'default_price_id' => $stripeProduct->default_price,
            'active'           => $stripeProduct->active,
        ])->save();

        $this->setAsStripeProduct($stripeProduct);

        return $this;
    }


    /**
     * Delete stripe product
     * 
     * Local query and Stripe Api connection
     * 
     * The stripe product will be disabled and the local register will be deleted
     * StripePHP api not allows delete products, you must delete it from the dashboard
     *
     * @return self
     */
    public function deleteStripeProduct()
    {        
        $stripeProduct = $this->getStripeClientConnection()->products->update($this->product_id, [
            'name'   => $this->name . ' (Deleted from PHP API) ',
            'active' => false
        ]);

        $this->delete();

        $this->setAsStripeProduct($stripeProduct);

        return $this;
    }

    /**
     * Active the stripe product
     *
     * Local query and Stripe Api connection
     * 
     * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
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
     */
    public function disableStripeProduct($serviceIntegrationId)
    {
        return $this->updateStripeProduct(['active' => false]);
    }    


    public function service_integration()
    {
        return $this->belongsTo(config('stripe-multiple-accounts.relationship_models.stripe_accounts'), 'service_integration_id');
    }

    public function stripe_subscription_items()
    {
        return $this->hasMany(config('stripe-multiple-accounts.relationship_models.subscription_items'), 's_product_id');
    }
}
