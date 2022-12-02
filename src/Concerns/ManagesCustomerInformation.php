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
     * @param int $stripeAccountId
     * @param array|null $opts
     * @return \Stripe\Customer|null
     */
    public function relateCustomerWithStripeAccount($serviceIntegrationId = null, $opts = [])
    {
        return $this->createStripeCustomer($serviceIntegrationId, $opts);
    }


    /**
     * Get the related customer to stripe
     * 
     * @param int $serviceIntegrationId
     * @param array|null $opts
     * @return \Stripe\Customer|null
     */
    public function getRelatedStripeCustomer($serviceIntegrationId = null, $opts = [])
    {   
        $stripeSecretKey = $this->getRelatedStripeSecretKey($serviceIntegrationId);

        if (is_null($stripeSecretKey)) {
            return ;
        }

        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        return (new StripeClient($stripeSecretKey))->customers->retrieve($stripeCustomerId, null, $opts);
    }
        
    /**
     * Get the stripe customer id (cus_..)
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
            $serviceIntegrationId = optional($this->resolveStripeServiceIntegration())->id;
        }

        if ($serviceIntegrationId == null) {
            return null;
        }

        return $this->stripeCustomerIdRecentlyFetched = DB::table(config('stripe-multiple-accounts.multiple_customers_id.customer_accounts_table'))
                    ->where(config('stripe-multiple-accounts.multiple_customers_id.foreign_stripe_integration_id'), $serviceIntegrationId)
                    ->value(config('stripe-multiple-accounts.multiple_customers_id.customer_id_column'));
    }

    /**
     * Create or get the related stripe customer instance
     * 
     * @param int $stripeAccountId
     * @return \Stripe\Customer|null
     */
    public function createOrGetRelatedStripeCustomer($serviceIntegrationId = null, $opts = [])
    {
        if ($this->stripeCustomerRecentlyFetched instanceof Customer) {
            return $this->stripeCustomerRecentlyFetched;
        }    
    
        $stripeSecretKey = $this->getRelatedStripeSecretKey($serviceIntegrationId);
        
        if (is_null($stripeSecretKey)) {
            return ;
        }        
    
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);
    
        if (is_null($stripeCustomerId)) {
            return $this->createStripeCustomer($serviceIntegrationId, $opts);
        }

        return $this->getRelatedStripeCustomer($serviceIntegrationId, $opts);
    }
    
    /**
     * Create a stripe customer with the current user data
     * 
     * @param int|null $serviceIntegrationId
     * @param array $opts
     * @return \Stripe\Customer|null
     */
    public static function createStripeCustomer($serviceIntegrationId = null, $opts = [])
    {
        $stripeSecretKey = $this->getRelatedStripeSecretKey($serviceIntegrationId);

        if (is_null($stripeSecretKey)) {
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

        return (new StripeClient($stripeSecretKey))->customers->create($opts);
    }

}
