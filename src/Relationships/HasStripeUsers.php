<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Models\StripeCustomer;

trait HasStripeUsers
{

  /**
   * Boot on delete method
   */
  public static function bootHasStripeUsers()
  {
    static::deleting(function ($model) {
      if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
        return;
      }

      // Preserve the stripe users
      $model->stripe_customers()->query()->update([
        'owner_id'   => null,
        'owner_type' => null
      ]);
    });
  }

  /**
  * Get the stripe_customers
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function stripe_customers()
  {
    return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.customer', StripeCustomer::class), 'owner');
  }
}
