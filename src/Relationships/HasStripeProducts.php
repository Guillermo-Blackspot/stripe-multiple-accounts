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

  public function newStripeProduct($serviceIntegrationId, $productName)
  {
    return new ProductBuilder($this, $serviceIntegrationId, $productName);
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
