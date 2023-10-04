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
    use \Blackspot\StripeMultipleAccounts\BillableForStripe;

    class User extends Model {
        use BillableForStripe;
    }
```
...

<br>

All available methods

<br>

...
```php
$user = User::first();
$subsidiary = Subsidiary::with('service_integrations');


// Customer
$subsidiary->stripe->customerExists($user)
$subsidiary->stripe->getCustomer($user)
$subsidiary->stripe->getCustomerId($user)
$subsidiary->stripe->createCustomerIfNotExists($user)
$subsidiary->stripe->createOrGetCustomer($user)
$subsidiary->stripe->createCustomer($user)
$subsidiary->stripe->updateCustomer($user)
$subsidiary->stripe->assertCustomerExists($user)

// Charges

$subsidiary->stripe->findPaymentIntent($user, $paymentIntentId)
$subsidiary->stripe->makeCharge($user, $amount, $paymentMethodId, array $opts = [])
$subsidiary->stripe->pay($user, $amount, array $opts = [])
$subsidiary->stripe->createPaymentIntentWith($user, $amount, array $paymentMethods, array $opts = [])
$subsidiary->stripe->createPaymentIntent($user, $amount, array $opts = [])
$subsidiary->stripe->refundPaymentIntent($user, $paymentIntentId, array $opts = [])


// Payment methods 
$subsidiary->stripe->createSetupIntent($user, $opts = [])
$subsidiary->stripe->getPaymentMethods($user, $type = 'card')
$subsidiary->stripe->addPaymentMethod($user, $paymentMethodId)
$subsidiary->stripe->deletePaymentMethod($user, $paymentMethodId)
$subsidiary->stripe->getOrAddPaymentMethod($user, $paymentMethodId, $type = 'card')
$subsidiary->stripe->setDefaultPaymentMethod($user, $paymentMethodId)
$subsidiary->stripe->getDefaultPaymentMethod($user)

// Credentials
$subsidiary->stripe->getClient()
$subsidiary->stripe->getSecretKey()
$subsidiary->stripe->getPublicKey()

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