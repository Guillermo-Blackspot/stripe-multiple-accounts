<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use App\Models\Morphs\ServiceIntegration;

trait HasServiceIntegrations
{
  /**
   * Boot on delete method
   */
  public static function bootHasServiceIntegrations()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->service_integrations()->delete();
    });
  }

  /**
  * Get the service_integrations
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function service_integrations()
  {
    return $this->morphMany(ServiceIntegration::class, 'owner');
  }
}
