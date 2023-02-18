<?php

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\StripeMultipleAccounts\SubscriptionBuilder;

trait ManagesSubscriptions
{
    /**
     * Constructor
     * 
     * @param int|null  $serviceIntegrationId
     * @param string  $identifier
     * @param string  $name
     * @param Illuminate\Database\Eloquent\Model|Illuminate\Database\Eloquent\Model<\BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationProduct> $items
     * @return \BlackSpot\StripeMultipleAccounts\SubscriptionBuilder|null
     */
    public function newStripeSubscription($serviceIntegrationId = null, $identifier, $name, $items = [])
    {
        return new SubscriptionBuilder($this, $identifier, $name, $items, $serviceIntegrationId);
    }
}