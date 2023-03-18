<?php

use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Models\StripeProduct;
use BlackSpot\StripeMultipleAccounts\Models\StripeSubscription;
use BlackSpot\StripeMultipleAccounts\Models\StripeSubscriptionItem;
use BlackSpot\StripeMultipleAccounts\Models\StripeCustomer;

return [    

    /**
     * Copy this in the service-integrations-container.php config
     */

    'stripe_models' => [
        'local_user'         => \App\Models\User::class,
        'product'            => StripeProduct::class,
        'customer'           => StripeCustomer::class,
        'subscription'       => StripeSubscription::class,
        'subscription_item'  => StripeSubscriptionItem::class,
    ]
];
