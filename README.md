# Stripe Multiple Accounts

Support multiple stripe accounts

One user can have many "customers_id"

<br>

## Tables
<br>

## _ServiceIntegrations_ 
morph relation that contains the 
<br>
<br>

- stripe key
- stripe secret
- webhook secret

That means that the one model can manage one or more stripe accounts

In this case we have a "stub" files to create a implementation with Company Subsidiaries

<br>

## _UserServiceIntegrationAccount_ 
one to many relationship.
<br>
<br>

In this table we relate the user_id with the service_integration_id (Stripe or Another..) and with a extra column that contains the "real relation" or the "multiplicity" (account_id)

## Methods

<br>

the model needs use the ``\BlackSpot\StripeMultipleAccounts\Billable`` Trait
<br>

...
```php
    use \Blackspot\StripeMultipleAccounts\Billable;

    class User extends Model {
        use Billable;
    }
```
...

<br>

All available methods

<br>

...
```php

    /** Note: 
     * 
     * All the methods that fetch or send data to stripe 
     * can be throws exceptions
     * 
     * getRelatedStripeCustomer
     * createOrGetRelatedStripeCustomer
     * getRelatedStripeCustomerPaymentMethods
     * attachStripeCustomerPaymentMethodResource
     * detachStripeCustomerPaymentMethodResource
     */

    $user = \App\Models\User::first();

    // or

    $user = \Auth::user();

    //:  \Models\ServiceIntegration
    $user->resolveStripeServiceIntegration(); 
    
    $user->getStripeServiceIntegration();
    
    //:  String
    $user->getRelatedStripeSecretKey();

    $user->getRelatedStripePublicKey();

    //:  \Stripe\Customer|null
    $user->relateCustomerWithStripeAccount(); // Alias of createStripeCustomer

    $user->createOrGetRelatedStripeCustomer();
    
    $user->createStripeCustomer();

    $user->stripeCustomerExists(); // Determine if stripe customer exists

    $user->getRelatedStripeCustomer();

    $user->getRelatedStripeCustomerId(); // related customer_id

    //:   \Stripe\StripeClient
    $user->getStripeClientConnection();

    //:   PaymentMethod
    $user->getRelatedStripeCustomerPaymentMethods(); // \Stripe\Collection

    $user->attachStripeCustomerPaymentMethodResource(); // \Stripe\BankAccount|\Stripe\Card|\Stripe\Source|null

    $user->detachStripeCustomerPaymentMethodResource(); // \Stripe\PaymentMethod|null


    //:   void
    $user->clearStripeBillableCache(); // Clear the memory cache
```

...

## _Publishables_

_Config_
```
php artisan vendor:publish --tag=stripe-multiple-accounts:config
```

<br>

_Migrations_
```
php artisan vendor:publish --tag=stripe-multiple-accounts:migrations
```