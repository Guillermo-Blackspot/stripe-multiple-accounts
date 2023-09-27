<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Models\StripeSubscription;

trait HasStripeSubscriptions
{
  /**
   * Boot on delete method
   */
  public static function bootHasStripeSubscriptions()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }

        $model->stripe_subscriptions()->delete();
        
        $model->system_subscriptions()
              ->where('status', '!=', SystemSubscription::STATUS_UNLINKED)
              ->where('status', '!=', SystemSubscription::STATUS_CANCELED)
              ->update([
                'service_integration_id' => null,
                'status' => SystemSubscription::STATUS_UNLINKED
              ]);

      $model->system_payment_intents()->update(['service_integration_id' => null]);
    });
  }


  /**
   * Determe if the model has a suscription with the identifier 
   * in the local database
   *
   * @return bool
   */
  public function isSubscribedWithStripeTo($serviceIntegrationId = null, $identifier)
  {
    $serviceIntegrationId = $this->getStripeServiceIntegration($serviceIntegrationId)->id;

    return $this->stripe_subscriptions()->serviceIntegration($serviceIntegrationId)->where('identified_by', $identifier)->exists();
  }

  /**
   * Find one subscription that belongs to the model by identifier 
   * in the local database
   *
   * @return BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription
   */
  public function findStripeSubscriptionByIdentifier($serviceIntegrationId = null, $identifier, $with = [])
  {
    $serviceIntegrationId = $this->getStripeServiceIntegration($serviceIntegrationId)->id;

    return $this->stripe_subscriptions()->serviceIntegration($serviceIntegrationId)->with($with)->where('identified_by', $identifier)->first();
  }


  /**
  * Get the stripe_subscriptions
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function stripe_subscriptions()
  {
    return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.subscription', StripeSubscription::class), 'owner');
  }
}
