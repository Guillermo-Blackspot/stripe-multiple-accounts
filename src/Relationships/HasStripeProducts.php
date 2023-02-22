<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

trait HasStripeProducts
{
  /**
   * Boot on delete method
   */
  public static function bootHasStripeProducts()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model->stripe_products()->delete();
    });
  }

  /**
  * Get the stripe_products
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function stripe_products()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.products'), 'owner');
  }
}
