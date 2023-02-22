<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

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
    return $this->stripe_subscriptions()->where('identified_by', $identifier)->exists();
  }

  /**
   * Find one subscription that belongs to the model by identifier 
   * in the local database
   *
   * @return BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription
   */
  public function findSubscriptionByIdentifier($identifier, $with = [])
  {
    return $this->stripe_subscriptions()->with($with)->where('identified_by', $identifier)->first();
  }


  /**
  * Get the stripe_subscriptions
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function stripe_subscriptions()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.subscriptions'), 'owner');
  }
}
