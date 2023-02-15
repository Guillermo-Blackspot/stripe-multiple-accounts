<?php

namespace BlackSpot\StripeMultipleAccounts\Concerns;

trait ManagesServiceIntegrationSubscriptions
{




    // /**
    //  * Begin creating a new subscription.
    //  *
    //  * @param  string  $name
    //  * @param  string|string[]  $prices
    //  * @return \Laravel\Cashier\SubscriptionBuilder
    //  */
    // public function newSubscription($name, $prices = [])
    // {
    //     return new SubscriptionBuilder($this, $name, $prices);
    // }

    /**
     * Determine if the Stripe model is on trial.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $subscriptionIdentifier
     * @return bool
     */
    public function stripeSubscriptionOnTrial($serviceIntegrationId = null, $subscriptionIdentifier)
    {
        $subscription = $this->getLocalStripeSubscription($serviceIntegrationId, $subscriptionIdentifier);

        if (is_null($subscription)) {
            return false;
        }

        return $subscription->onTrial();
    }

    /**
     * Determine if the Stripe model's trial has ended.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $subscriptionIdentifier
     * @return bool
     */
    public function stripeSubscriptionHasExpiredTrial($serviceIntegrationId = null, $subscriptionIdentifier)
    {        
        $subscription = $this->getLocalStripeSubscription($serviceIntegrationId, $subscriptionIdentifier);

        if (is_null($subscription)) {
            return false;
        }

        return $subscription->hasExpiredTrial();
    }

    // /**
    //  * Determine if the Stripe model is on a "generic" trial at the model level.
    //  *
    //  * @return bool
    //  */
    // public function stripeSubscriptionOnGenericTrial()
    // {
    //     return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    // }

    // /**
    //  * Filter the given query for generic trials.
    //  *
    //  * @param  \Illuminate\Database\Eloquent\Builder  $query
    //  * @return void
    //  */
    // public function scopeOnGenericTrial($query)
    // {
    //     $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', Carbon::now());
    // }

    // /**
    //  * Determine if the Stripe model's "generic" trial at the model level has expired.
    //  *
    //  * @return bool
    //  */
    // public function stripeSubscriptionHasExpiredGenericTrial()
    // {
    //     return $this->trial_ends_at && $this->trial_ends_at->isPast();
    // }

    // /**
    //  * Filter the given query for expired generic trials.
    //  *
    //  * @param  \Illuminate\Database\Eloquent\Builder  $query
    //  * @return void
    //  */
    // public function scopeHasExpiredGenericTrial($query)
    // {
    //     $query->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', Carbon::now());
    // }

    // /**
    //  * Get the ending date of the trial.
    //  *
    //  * @param  string  $name
    //  * @return \Illuminate\Support\Carbon|null
    //  */
    // public function trialEndsAt($name = 'default')
    // {
    //     if (func_num_args() === 0 && $this->stripeSubscriptionOnGenericTrial()) {
    //         return $this->trial_ends_at;
    //     }

    //     if ($subscription = $this->subscription($name)) {
    //         return $subscription->trial_ends_at;
    //     }

    //     return $this->trial_ends_at;
    // }

    /**
     * Determine if the Stripe model has a given subscription.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $subscriptionIdentifier
     * @return bool
     */
    public function stripeIsSubscribed($serviceIntegrationId = null, $subscriptionIdentifier)
    {
        $subscription = $this->getLocalStripeSubscription($serviceIntegrationId, $subscriptionIdentifier);

        if (is_null($subscription)) {
            return false;
        }

        return $subscription->valid();
    }

    /**
     * Determine if the customer's subscription has an incomplete payment.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $subscriptionIdentifier
     * @return bool
     */
    public function stripeSubscriptionHasIncompletePayment($serviceIntegrationId = null, $subscriptionIdentifier)
    {
        if ($subscription = $this->getLocalStripeSubscription($serviceIntegrationId, $subscriptionIdentifier)) {
            return $subscription->hasIncompletePayment();
        }

        return false;
    }

    // /**
    //  * Determine if the Stripe model is actively subscribed to one of the given products.
    //  *
    //  * @param  string|string[]  $products
    //  * @param  string  $name
    //  * @return bool
    //  */
    // public function subscribedToProduct($products, $name = 'default')
    // {
    //     $subscription = $this->subscription($name);

    //     if (! $subscription || ! $subscription->valid()) {
    //         return false;
    //     }

    //     foreach ((array) $products as $product) {
    //         if ($subscription->hasProduct($product)) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    // /**
    //  * Determine if the Stripe model is actively subscribed to one of the given prices.
    //  *
    //  * @param  string|string[]  $prices
    //  * @param  string  $name
    //  * @return bool
    //  */
    // public function subscribedToPrice($prices, $name = 'default')
    // {
    //     $subscription = $this->subscription($name);

    //     if (! $subscription || ! $subscription->valid()) {
    //         return false;
    //     }

    //     foreach ((array) $prices as $price) {
    //         if ($subscription->hasPrice($price)) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    // /**
    //  * Determine if the customer has a valid subscription on the given product.
    //  *
    //  * @param  string  $product
    //  * @return bool
    //  */
    // public function onProduct($product)
    // {
    //     return ! is_null($this->subscriptions->first(function (Subscription $subscription) use ($product) {
    //         return $subscription->valid() && $subscription->hasProduct($product);
    //     }));
    // }

    // /**
    //  * Determine if the customer has a valid subscription on the given price.
    //  *
    //  * @param  string  $price
    //  * @return bool
    //  */
    // public function onPrice($price)
    // {
    //     return ! is_null($this->subscriptions->first(function (Subscription $subscription) use ($price) {
    //         return $subscription->valid() && $subscription->hasPrice($price);
    //     }));
    // }

    /**
     * Find the customer local subscription 
     * 
     * @param int|null  $serviceIntegrationId
     * @param string  $subscriptionIdentifier
     * @param null|\Closure  $queryBuilder
     * 
     * @return \BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription
     */
    public function getLocalStripeSubscription($serviceIntegrationId = null, $subscriptionIdentifier, $queryBuilder = null)
    {        
        $serviceIntegration = $this->getStripeServiceIntegration($serviceIntegrationId); 
        
        if (is_null($serviceIntegration)) {
            return ;
        }

        $serviceIntegrationId = $serviceIntegration->id;

        $query = $this->service_integration_subscriptions()
                    ->query()
                    ->where('identify_by', $subscriptionIdentifier)
                    ->where('service_integration_id', $serviceIntegrationId);

        if (!is_null($queryBuilder)) {
            return $queryBuilder($query)->first();
        }
        return $query->first();
    }

}
