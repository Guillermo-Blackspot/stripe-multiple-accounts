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

    $serviceIntegrationId = 1; // sucursal merida con cuenta de stripe 1;

    // Siempre se pasa como primer parametro la sucursal o cuenta de stripe el service_integration_id que tiene el payload de stripe
    // Puede ser nulo y tomara la sucursal que este definida por metodos magicos 
    // Estos metodos magicos deben definirse en el modelo Que use el trait "ManagesAuthCredentials.php"
    /* 
        if (isset($this->id) && self::class == config('stripe-multiple-accounts.relationship_models.stripe_accounts')) {
            // Si el modelo es ServiceIntegration definido en el config, tomara el id
        }else if (isset($this->service_integration_id)){
            // Sel el modelo tiene un "service_integration_id" relacionado tomara ese atributo
        }else if (method_exists($this, 'getStripeServiceIntegrationId')){
            // Si el modelo tiene un metodo llamado "getStripeServiceIntegrationId" tomara el valor de esa funcion
        }else if (method_exists($this, 'getStripeServiceIntegrationOwnerId') && method_exists($this,'getStripeServiceIntegrationOwnerType')){
            // Si el modelo tiene un metodo llamado "getStripeServiceIntegrationOwnerId" y "getStripeServiceIntegrationOwnerType" tomara el valor de esas funciones 
            // y las aplicara en un where
            // Esto se usa cuando a los usuarios no se les asigna un servicio de stripe directamente si no por otro modelo
            // entonces si el usuario cuenta con una sucursal y la sucursal tiene un servicio de stripe
            // se puede determinar que la cuenta de stripe es la primera que encuentre en asociada a la sucursal del usuario dado la columna "owner" de service_integrations
            
            function getStripeServiceIntegrationOwnerId(){
                return $this->subsidiary->id;
            }
            function getStripeServiceIntegrationOwnerId(){
                return '\App\Models\Subsidiary';
            }
        }     
    */
    // stripe_key
    // stripe_secret, etc..

    $user = \Auth::user();

    // ManagesAuthCredentials.php

    $user->getStripeClient($serviceIntegrationId);            // nullable
    $user->getStripeServiceIntegration($serviceIntegrationId);          // nullable
    $user->assertStripeServiceIntegrationExists($serviceIntegrationId); // terminal
    $user->getStripeSecretKey($serviceIntegrationId);            // nullable
    $user->getStripePublicKey($serviceIntegrationId);            // nullable

    // ManagesCustomer.php
    $user->stripeCustomerExists($serviceIntegrationId);                        // bool
    $user->getStripeCustomer($serviceIntegrationId, $opts = []);               // nullable
    $user->getStripeCustomerId($serviceIntegrationId);                         // nullable
    $user->createStripeCustomerIfNotExists($serviceIntegrationId, $opts = []); // nullable
    $user->createOrGetStripeCustomer($serviceIntegrationId, $opts = []);       // nullable
    $user->updateStripeCustomer($serviceIntegrationId, $opts = []);            // nullable
    $user->setStripeCustomerToCache(new \Stripe\Customer);                     // void
    $user->setStripeCustomerIdToCache($customerId);                            // void

    // ManagesPaymentMethods.php
    $user->createStripeSetupIntent($serviceIntegrationId, $opts = []);                           // nullable
    $user->getStripePaymentMethods($serviceIntegrationId, $type = 'card');                       // \Stripe\Collection
    $user->addStripePaymentMethod($serviceIntegrationId, $paymentMethodId);                      // nullable
    $user->deleteStripePaymentMethod($serviceIntegrationId, $paymentMethodId);                   // nullable
    $user->getOrAddStripePaymentMethod($serviceIntegrationId, $paymentMethodId, $type = 'card'); // nullable
    $user->setDefaultStripePaymentMethod($serviceIntegrationId, $paymentMethodId);               // nullable alias of updateDefaultStripePaymentMethod
    $user->updateDefaultStripePaymentMethod($serviceIntegrationId, $paymentMethodId);            // nullable
    $user->getDefaultStripePaymentMethod($serviceIntegrationId, $paymentMethodId);               // nullable

    // ManagesPaymentMethodSources.php
    $user->addStripePaymentMethodSource($serviceIntegrationId = null, $tokenId, $opts = [])      // nullable - LEGACY
    $user->deleteStripePaymentMethodSource($serviceIntegrationId = null, $sourceId, $opts = [])  // nullable - LEGACY

    // PerformsCharges.php
    $user->makeStripeCharge($serviceIntegrationId, $amount, $paymentMethodId, $opts = []);                               // nullable
    $user->stripePay($serviceIntegrationId, $amount, $opts = []);                                                        // nullable
    $user->createStripePaymentIntentWith($serviceIntegrationId, $amount, $paymentMethods = ['card','oxxo'], $opts = []); // nullable
    $user->createStripePaymentIntent($serviceIntegrationId, $amount, $opts = []);                                        // nullable
    $user->refundStripePaymentIntent($serviceIntegrationId, $paymentIntentId, $opts = []);                               // nullable
    $user->findStripePaymentIntent($serviceIntegrationId, $paymentIntentId);                                             // nullable

    // ManagesSubscriptions.php

    $identifier = "usr_{$userId}_prod_{$productId}";
    $name       = 'Plan anual';
    $items      = \BlackSpot\StripeMultipleAccounts\Models\StripeProducts::get(['id','allow_recurring','default_price_id','service_integration_id']); 

    $user->newStripeSubscription($serviceIntegrationId, $identifier, $name, $items); // nullable


    /** @var \BlackSpot\StripeMultipleAccounts\Models\StripeUser */
    $stripeUser = user()->stripe_customers()->where('service_integration_id', $serviceIntegrationId)->first();

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