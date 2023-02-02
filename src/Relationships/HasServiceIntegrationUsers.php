<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use App\Models\Morphs\ServiceIntegrationUser;

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
    return $this->morphMany(ServiceIntegrationUser::class, 'model');
  }
}
