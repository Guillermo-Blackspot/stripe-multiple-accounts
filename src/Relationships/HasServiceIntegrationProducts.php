<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

trait HasServiceIntegrationProducts
{
  /**
   * Boot on delete method
   */
  public static function bootHasServiceIntegrationProducts()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->service_integration_products()->delete();
    });
  }

  /**
  * Get the service_integration_products
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function service_integration_products()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.products'), 'owner');
  }
}
