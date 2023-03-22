<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Models\StripeCustomer;
use BlackSpot\StripeMultipleAccounts\Relationships\HasStripeUsers;
use Illuminate\Support\Facades\DB;
use Stripe\Customer;
use Stripe\StripeClient;

/**
 * Manages customer information
 * 
 * @property $stripeCustomerRecentlyFetched \Stripe\Customer|null
 * @property $stripeCustomerIdRecentlyFetched \Stripe\Customer|null
 * 
 * @method stripeCustomerExists($serviceIntegrationId = null): bool
 * 
 * @method relateCustomerWithStripeAccount($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 * @method getRelatedStripeCustomer($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 * @method getRelatedStripeCustomerId($serviceIntegrationId = null): null|string
 * @method createOrGetRelatedStripeCustomer($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 * @method createStripeCustomer($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 */
trait ManagesCustomer
{
    /**
     * Boot on delete method
     */
    public static function bootManagesCustomer()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            // Preserve the stripe users
            $model->stripe_customers()->query()->update([
                'owner_id'   => null,
                'owner_type' => null
            ]);
        });
    }

    /**
     * Get the stripe_customers
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stripe_customers()
    {
        return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.customer', StripeCustomer::class), 'owner');
    }

    /**
     * The stripe customer recently fetched or created
     *
     * LocalDatabase
     * 
     * @var \BlackSpot\StripeMultipleAccounts\Models\StripeCustomer|null
     */
    protected $localDatabaseStripeCustomerRecentlyFetched = [];

    /**
     * Determine if the user exists as stripe customer in the service integration
     * 
     * LocalDatabase
     * Uses the property cache
     * 
     * @param int|null $serviceIntegrationId
     * @return bool
     */
    public function stripeCustomerExists($serviceIntegrationId = null)
    {
        try {
            $this->asLocalStripeCustomer($serviceIntegrationId);
            return true;
        } catch (InvalidStripeServiceIntegration $err) {
            return false;
        } catch (InvalidStripeCustomer $err) {
            return false;
        }
    }

    /**
     * Get the related customer to stripe
     * 
     * Connect to local database and Fetch to stripe
     *
     * Store result in to memo
     * 
     * @param int|null $serviceIntegrationId
     * @param array $expand
     * @return \Stripe\Customer|null
     * 
     * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function getStripeCustomer($serviceIntegrationId = null, array $expand = [])
    {            
        return $this->asLocalStripeCustomer($serviceIntegrationId)->asStripe($expand);
    }
        
    /**
     * Get the stripe customer id (cus_..)
     * 
     * Connect to local database
     * Store result in to memo
     * 
     * @param int|null $serviceIntegrationId
     * @return string|null
     * 
     * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function getStripeCustomerId($serviceIntegrationId = null)
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->customer_id;
    }


    /**
     * Sync to the stripe and create the customer if not exists
     * 
     * Connect to local database
     * Connect to stripe if not exists
     * 
     * returns null if exists 
     * returns \Stripe\Customer if was created
     * Store the result in to memo
     * 
     * @param int|null  $serviceIntegrationId
     * @param array  $opts
     * 
     * @return \Stripe\Customer|null  
     * 
     * @throws InvalidStripeServiceIntegration
     */
    public function createStripeCustomerIfNotExists($serviceIntegrationId = null, array $opts = [])
    {
        $localStripeCustomer = $this->getFromLocalDatabaseStripeCustomer($serviceIntegrationId);

        // Exists
        if (! is_null($localStripeCustomer)) {
            return null;
        }

        // Was Created
        return $this->createStripeCustomer($serviceIntegrationId, $opts);
    }

    /**
     * Create or get the related stripe customer instance
     * 
     * Connect to local database
     * Connect to stripe
     * Store result in to memo
     * 
     * @param int|null $serviceIntegrationId
     * @return \Stripe\Customer|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function createOrGetStripeCustomer($serviceIntegrationId = null, array $opts = [])
    {
        $localStripeCustomer = $this->getFromLocalDatabaseStripeCustomer($serviceIntegrationId);

        if (! is_null($localStripeCustomer)) {
            return $localStripeCustomer->asStripe();
        }

        return $this->createStripeCustomer($serviceIntegrationId, $opts);
    }
    
    /**
     * Create a stripe customer with the current user data
     * 
     * Connect to stripe
     * Connect to local database
     * Store result in to memo
     * 
     * @param int|null $serviceIntegrationId
     * @param array $opts
     * @return \Stripe\Customer|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function createStripeCustomer($serviceIntegrationId = null, array $opts = [])
    {         
        // check if exists 
        $localStripeCustomer = $this->getFromLocalDatabaseStripeCustomer($serviceIntegrationId);

        if (! is_null($localStripeCustomer)) {
            return $localStripeCustomer->asStripe();
        }

        // if not exists create it

        $serviceIntegration   = $this->getStripeServiceIntegration($serviceIntegrationId);
        $serviceIntegrationId = $serviceIntegration->id;
        $stripe               = $this->getStripeClientConnection($serviceIntegrationId);
    
        if (empty($opts)) {
            if (method_exists($this, 'getStripeCustomerInformation')) {
                $opts = (array) $this->getStripeCustomerInformation();
            }
        }

        if (! isset($opts)) {
            $opts['metadata'] = [];
        }
            
        $opts['metadata'] = array_merge($opts['metadata'], [                
            'service_integration_id'   => $serviceIntegrationId,
            'service_integration_type' => ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class),
        ]);

        // Connect to stripe
        $stripeCustomer = $stripe->customers->create($opts);

        // Connect to local database
        $localStripeCustomer = $this->stripe_customers()->create([
            'service_integration_id' => $serviceIntegration->id,
            'customer_id'            => $stripeCustomer->id
        ]);

        $localStripeCustomer
            ->putServiceIntegrationFound($serviceIntegration)
            ->putStripeCustomer($stripeCustomer);

        return $stripeCustomer;
    }


    /**
     * Update the underlying Stripe customer information for the model.
     *
     * @param int|null $serviceIntegrationId
     * @param  array  $opts
     * @return \Stripe\Customer
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function updateStripeCustomer($serviceIntegrationId = null, array $opts = [])
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->updateAsStripe($opts);
    }

    /**
     * Get the local stripe customer
     *
     * @param int $serviceIntegrationId
     * @return \BlackSpot\StripeMultipleAccounts\StripeCustomer
     * 
     * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
     */    
    public function asLocalStripeCustomer($serviceIntegrationId = null)
    {
        $this->assertCustomerExists($serviceIntegrationId);

        $localStripeCustomer = $this->getFromLocalDatabaseStripeCustomer($serviceIntegrationId);

        $localStripeCustomer->assertExistsAsStripe();

        return $localStripeCustomer;
    }

    /**
     * Get from the local database the stripe customer
     *
     * @param int $serviceIntegrationId
     * @return \BlackSpot\StripeMultipleAccounts\Models\StripeCustomer
     * 
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    protected function getFromLocalDatabaseStripeCustomer($serviceIntegrationId = null)
    {           
        $serviceIntegration   = $this->getStripeServiceIntegration($serviceIntegrationId);
        $serviceIntegrationId = $serviceIntegration->id;

        if (isset($this->localDatabaseStripeCustomerRecentlyFetched[$serviceIntegrationId])) {
            return $this->localDatabaseStripeCustomerRecentlyFetched[$serviceIntegrationId];
        }

        $localStripeCustomer = $this->stripe_customers()->serviceIntegration($serviceIntegrationId)->first();

        if (is_null($localStripeCustomer)) {
            unset($this->localDatabaseStripeCustomerRecentlyFetched[$serviceIntegrationId]);
    
            return null;            
        }

        $localStripeCustomer->putServiceIntegrationFound($serviceIntegration);

        return $this->localDatabaseStripeCustomerRecentlyFetched[$serviceIntegrationId] = $localStripeCustomer;
    }

    /**
     * Determine if the customer has a Stripe customer ID and throw an exception if not.
     *
     * @param int|null $serviceIntegrationId
     * @return \BlackSpot\StripeMultipleAccounts\StripeCustomer
     *
     * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function assertCustomerExists($serviceIntegrationId = null)
    {
        $localStripeCustomer = $this->getFromLocalDatabaseStripeCustomer($serviceIntegrationId);
        
        if (is_null($localStripeCustomer)) {
            throw InvalidStripeCustomer::notYetCreated($this);
        }
    }

}
