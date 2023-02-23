<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\StripeMultipleAccounts\ProductBuilder;

trait HasStripeProducts
{
  /**
   * Boot on delete method
   */
  public static function bootHasStripeProducts()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->stripe_products()->delete();
    });
  }

  /**
   * Create a stripe product with this model
   *
   * @param int $serviceIntegrationId
   * @param string $productName
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function newStripeProduct($serviceIntegrationId, $productName)
  {
    return new ProductBuilder($this, $serviceIntegrationId, $productName);
  }

  /**
   * Determine if the model was synced with stripe
   *
   * Local query
   * 
   * @param int $serviceIntegrationId
   * @return bool
   */
  public function syncedWithStripe($serviceIntegrationId)
  {
    return $this->stripe_products()->where('service_integration_id', $serviceIntegrationId)->exists();
  }

  /**
   * Get the model was synced with stripe in the local database
   *
   * Local query
   * 
   * @param int $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function findStripeProduct($serviceIntegrationId)
  {
    return $this->stripe_products()->where('service_integration_id', $serviceIntegrationId)->first();
  }


  /**
   * Update the model synced with stripe
   *
   * Local query and Stripe Api connection
   * 
   * @param int $serviceIntegrationId
   * @param array $opts
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function updateStripeProduct($serviceIntegrationId, $opts)
  {
    return $this->findStripeProduct($serviceIntegrationId)->updateStripeProduct($opts);
  }

  /**
   * Delete the model synced with stripe
   *
   * Local query and Stripe Api connection
   * 
   * The stripe product will be disabled and the local register will be deleted
   * StripePHP api not allows delete products, you must delete it from the dashboard
   * 
   * @param int $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function deleteStripeProduct($serviceIntegrationId)
  {
    return $this->findStripeProduct($serviceIntegrationId)->deleteStripeProduct();
  }

  /**
   * Active the stripe product
   *
   * Local query and Stripe Api connection
   * 
   * @param int $serviceIntegrationId
   * @param array $opts
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function activeStripeProduct($serviceIntegrationId)
  {
    return $this->updateStripeProduct($serviceIntegrationId, ['active' => true]);
  }

  /**
   * Disable the stripe product
   *
   * Local query and Stripe Api connection
   * 
   * @param int $serviceIntegrationId
   * @param array $opts
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function disableStripeProduct($serviceIntegrationId)
  {
    return $this->updateStripeProduct($serviceIntegrationId, ['active' => false]);
  }

  /**
  * Get the stripe_products
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function stripe_products()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.products'), 'model');
  }
}
