<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

trait HasServiceIntegrationUsers
{
  /**
   * Boot on delete method
   */
  public static function bootHasServiceIntegrationUsers()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->service_integration_users()->delete();
    });
  }

  /**
  * Get the service_integration_users
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function service_integration_users()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.customers'), 'owner');
  }
}
