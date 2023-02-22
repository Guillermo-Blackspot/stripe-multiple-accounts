<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

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
        $model->stripe_users()->delete();
    });
  }

  /**
  * Get the stripe_users
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function stripe_users()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.customers'), 'owner');
  }
}
