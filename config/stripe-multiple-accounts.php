<?php

return [    

    /**
     * Relationship table between \App\Models\User and the ServiceIntegration (stripe)
     * 
     */
    'multiple_customers_id' => [
        
        /**
         * The table that contains the relation between the user and the stripe account
         */
        'customer_accounts_table' => 'user_s_integrations_accounts',
        
        /**
         * The column name that contains the customer_id ("cus_") related with the users
         */
        'customer_id_column' => 'account_id',
    
        /**
         * The foreign key for the stripe integration
         */
        'foreign_stripe_integration_id' => 'service_integration_id',
    
    ],

    'stripe_integrations' => [
        
        /**
         * The model that contains the stripe keys
         * 
         * secret_key, 
         * public_key
         * webhook_key
         */
        'table' => 'service_integrations',

        /**
         * The service integrations primary key
         */
        'primary_key' => 'id',

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
