<?php

use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationProduct;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscriptionItem;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationUser;

return [    

    /**
     * Models
     */
    'relationship_models' => [
        'stripe_accounts'    => ServiceIntegration::class,
        'products'           => ServiceIntegrationProduct::class,
        'customers'          => ServiceIntegrationUser::class,
        'subscriptions'      => ServiceIntegrationSubscription::class,
        'subscription_items' => ServiceIntegrationSubscriptionItem::class,
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
