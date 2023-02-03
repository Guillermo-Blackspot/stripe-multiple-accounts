<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use Illuminate\Support\Facades\DB;
use Stripe\Customer;
use Stripe\StripeClient;

/**
 * Manages customer information
 * 
 * @property $stripeCustomerRecentlyFetched \Stripe\Customer|null
 * @property $stripeCustomerIdRecentlyFetched \Stripe\Customer|null
 * 
 * @method relateCustomerWithStripeAccount($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 * @method getRelatedStripeCustomer($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 * @method getRelatedStripeCustomerId($serviceIntegrationId = null): null|string
 * @method createOrGetRelatedStripeCustomer($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 * @method createStripeCustomer($serviceIntegrationId = null, $opts = []): \Stripe\Customer|null
 */
trait ManagesCustomerInformation
{
    /**
     * The customer instance recently fetched or created from stripe
     * @var \Stripe\Customer
     */
    protected $stripeCustomerRecentlyFetched = null;

    /**
     * The customer id recently fetched or created from stripe
     * @var \Stripe\Customer
     */    
    protected $stripeCustomerIdRecentlyFetched = null;


    /**
     * Create customer in stripe with the current user data
     * 
     * Send data to stripe
     * Store result in to cache
     * 
     * @param int|null $serviceIntegrationId
     * @param array|null $opts
     * @return \Stripe\Customer|null
     */
    public function relateCustomerWithStripeAccount($serviceIntegrationId = null, $opts = [])
    {
        return $this->createStripeCustomer($serviceIntegrationId, $opts);
    }

    /**
     * Determine if the user exists as customer in the service integration
     * 
     * Fetch to local database
     * 
     * @param int|null $serviceIntegrationId
     * @return boolean
     */
    public function stripeCustomerExists($serviceIntegrationId = null)
    {
        $this->service_integration_users()
                ->where('service_integration_id', $serviceIntegrationId)
                ->exists();
    }

    /**
     * Get the related customer to stripe
     * 
     * Fetch to stripe
     * Store result in to cache
     * 
     * @param int|null $serviceIntegrationId
     * @param array|null $opts
     * @return \Stripe\Customer|null
     */
    public function getRelatedStripeCustomer($serviceIntegrationId = null, $opts = [])
    {   
        if ($this->stripeCustomerRecentlyFetched instanceof Customer) {
            return $this->stripeCustomerRecentlyFetched;
        }

        $stripeClientConnection = $this->getStripeClientConnection();

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }

        $stripeCustomer = $stripeClientConnection->customers->retrieve($stripeCustomerId, null, $opts);

        $this->setStripeCustomerInstanceToCache($stripeCustomer);

        return $stripeCustomer;
    }
        
    /**
     * Get the stripe customer id (cus_..)
     * 
     * Fetch to local database
     * Store result in to cache
     * 
     * @param int|null $serviceIntegrationId
     * @return string|null
     */
    public function getRelatedStripeCustomerId($serviceIntegrationId = null)
    {
        if ($this->stripeCustomerIdRecentlyFetched !== null) {
            return $this->stripeCustomerIdRecentlyFetched;
        }

        if (is_null($serviceIntegrationId)) {
            $serviceIntegrationId = optional($this->resolveStripeServiceIntegration($serviceIntegrationId))->id;
        }

        if ($serviceIntegrationId == null) {
            return null;
        }
        
        $stripeCustomerId = $this->service_integration_users()
                                ->where('service_integration_id', $serviceIntegrationId)
                                ->value('customer_id');

        $this->setStripeCustomerIdToCache($stripeCustomerId);

        return $stripeCustomerId;
    }


    /**
     * Create if not exists the stripe customer
     * 
     * returns the \Stripe\Customer if was created or
     * returns null if exists
     * 
     * @param int|null  $serviceIntegrationId
     * 
     * @return \Stripe\Customer|null
     */
    public function createStripeCustomerIfNotExists($serviceIntegrationId = null, $opts = [])
    {
        if ($this->stripeCustomerExists($serviceIntegrationId)) {
            return null; // Exists
        }

        return $this->createStripeCustomer($serviceIntegrationId, $opts);
    }

    /**
     * Create or get the related stripe customer instance
     * 
     * Send or fetch to stripe
     * Store result in to cache
     * 
     * @param int|null $serviceIntegrationId
     * @return \Stripe\Customer|null
     */
    public function createOrGetRelatedStripeCustomer($serviceIntegrationId = null, $opts = [])
    {
        if ($this->stripeCustomerRecentlyFetched instanceof Customer) {
            return $this->stripeCustomerRecentlyFetched;
        }    
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);
        $stripeCustomer   = is_null($stripeCustomerId)
                                ? $this->createStripeCustomer($serviceIntegrationId, $opts)
                                : $this->getRelatedStripeCustomer($serviceIntegrationId, $opts);

        if (is_null($stripeCustomer)) {
            return null;
        }

        $this->setStripeCustomerInstanceToCache($stripeCustomer);

        return $stripeCustomer;
    }
    
    /**
     * Create a stripe customer with the current user data
     * 
     * Send to stripe
     * Store result in to cache
     * This function uses the updateOrCreate sentence in the local database
     * 
     * @param int|null $serviceIntegrationId
     * @param array $opts
     * @return \Stripe\Customer|null
     */
    public static function createStripeCustomer($serviceIntegrationId = null, $opts = [])
    { 
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        } 

        if ($opts instanceof self) {
            $address = $opts->main_address;

            $data = [
                'name'     => $opts->full_name,
                'metadata' => [
                    'user_id' => $opts->id,
                ]
            ];

            if ($address != null) {
                $data['address'] = [
                    'city'        => $address->city,
                    'country'     => 'MX',
                    'line1'       => $address->address,
                    'postal_code' => $address->postal_code,
                    'state'       => optional($address->state)->name,
                ];
            }

            if (isset($opts->email)) {
                $data['email'] = $opts->email;
            }
            if (isset($opts->cellphone)) {
                $data['phone'] = $opts->cellphone;
            }

            $opts = $data;
        }
        
        $stripeCustomer     = $stripeClientConnection->customers->create($opts);
        $serviceIntegration = $this->resolveStripeServiceIntegration($serviceIntegrationId);

        // Updating cache
        $this->setStripeCustomerInstanceToCache($stripeCustomer);

        // Creating or replacing the existing record the relationships in the local database
        $this->service_integration_users()->updateOrCreate([
            'service_integration_id' => $serviceIntegration->id,
            'customer_id'            => $stripeCustomer->id
        ]);

        return $stripeCustomer;
    }


    /**
     * Store the stripe customer instance in the stripeCustomerRecentlyFetched
     * 
     * @param \Stripe\Customer
     * @return void
     */
    public function setStripeCustomerInstanceToCache(Customer $stripeCustomer)
    {
        if ($stripeCustomer->id === null) {
            return ;
        }
        $this->stripeCustomerRecentlyFetched   = $stripeCustomer;
        $this->stripeCustomerIdRecentlyFetched = $stripeCustomer->id;
    }

    /**
     * Store the stripe customer id in the stripeCustomerIdRecentlyFetched attribute 
     * 
     * @param string
     * @return void
     */
    public function setStripeCustomerIdToCache($stripeCustomerId)
    {
        if ($stripeCustomerId == null || $stripeCustomerId == '') {
            return ;
        }

        $this->stripeCustomerIdRecentlyFetched = $stripeCustomerId;
    }


}
