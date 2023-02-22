<?php

use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Models\StripeProduct;
use BlackSpot\StripeMultipleAccounts\Models\StripeSubscription;
use BlackSpot\StripeMultipleAccounts\Models\StriprSubscriptionItem;
use BlackSpot\StripeMultipleAccounts\Models\StripeUser;

return [    

    /**
     * Models
     */
    'relationship_models' => [
        'stripe_accounts'    => ServiceIntegration::class,
        'products'           => StripeProduct::class,
        'customers'          => StripeUser::class,
        'subscriptions'      => StripeSubscription::class,
        'subscription_items' => StriprSubscriptionItem::class,
        'local_users'        => \App\Models\User::class
    ],
 

    'stripe_integrations' => [
        /**
         * Payload column
         * 
         * must be a json column
         */        
        'payload' => [
            'column'         => 'payload',
            'stripe_key'     => 'stripe_key',
            'stripe_secret'  => 'stripe_secret',
            'webhook_secret' => 'stripe_webhook_secret',
        ]
    ]


];
