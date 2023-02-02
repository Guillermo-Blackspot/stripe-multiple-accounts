<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use App\Models\Morphs\ServiceIntegrationSubscription;

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
  * Get the service_integration_subscriptions
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function service_integration_subscriptions()
  {
    return $this->morphMany(ServiceIntegrationSubscription::class, 'model');
  }
}
