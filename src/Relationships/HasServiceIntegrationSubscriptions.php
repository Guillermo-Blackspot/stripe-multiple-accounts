<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

trait HasServiceIntegrationSubscriptions
{
  /**
   * Boot on delete method
   */
  public static function bootHasServiceIntegrationSubscriptions()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->service_integration_subscriptions()->delete();
    });
  }


  /**
   * Determe if the model has a suscription with the identifier 
   * in the local database
   *
   * @return bool
   */
  public function isSubscribedTo($identifier)
  {
    return $this->service_integration_subscriptions()->where('identified_by', $identifier)->exists();
  }

  /**
   * Find one subscription that belongs to the model by identifier 
   * in the local database
   *
   * @return BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription
   */
  public function findSubscriptionByIdentifier($identifier, $with = [])
  {
    return $this->service_integration_subscriptions()->with($with)->where('identified_by', $identifier)->first();
  }


  /**
  * Get the service_integration_subscriptions
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function service_integration_subscriptions()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.subscriptions'), 'owner');
  }
}
