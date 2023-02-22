<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use Illuminate\Support\Facades\DB;
use Stripe\Collection;
use Stripe\StripeClient;

/**
 * Manages the auth credentials to connect with stripe
 * 
 * @method getStripeClientConnection($serviceIntegrationId = null)
 * @method getStripeServiceIntegrationQuery($serviceIntegrationId = null)
 * @method getStripeServiceIntegration($serviceIntegrationId = null)
 * @method assertStripeServiceIntegrationExists($serviceIntegrationId = null)
 * @method getRelatedStripeSecretKey($serviceIntegrationId = null)
 * @method getRelatedStripePublicKey($serviceIntegrationId = null)
 */
trait ManagesAuthCredentials
{
    private $stripeServiceIntegrationRecentlyFetched = null;
    
    /**
     * Get the stripe client connection
     * 
     * @param int|null $serviceIntegrationId
     * @return \Stripe\StripeClient|null
     */
    public function getStripeClientConnection($serviceIntegrationId = null)
    {
        $stripeSecretKey = $this->getRelatedStripeSecretKey($serviceIntegrationId);

        if ($stripeSecretKey == null) {
            return ;
        }

        return new StripeClient($stripeSecretKey);
    }

    /**
     * Get the stripe service integration query
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return \Illuminate\Database\Query\Builder|null
     */
    protected function getStripeServiceIntegrationQuery($serviceIntegrationId = null)
    {
        $serviceIntegrationModel     = config('stripe-multiple-accounts.relationship_models.stripe_accounts');
        $serviceIntegrationTableName = $serviceIntegrationModel::TABLE_NAME;
        $query                       = DB::table($serviceIntegrationTableName)
                                        ->where('name', 'Stripe')
                                        ->where('short_name', 'str');

        if (!is_null($serviceIntegrationId)) {
            $query = $query->where('id', $serviceIntegrationId);
        }elseif (isset($this->id) && self::class == config('stripe-multiple-accounts.relationship_models.stripe_accounts')) {
            $query = $query->where('id', $this->id);
        }else if (isset($this->service_integration_id)){
            $query = $query->where('id', $this->service_integration_id);
        }else if (method_exists($this, 'getStripeServiceIntegrationId')){
            $query = $query->where('id', $this->getStripeServiceIntegrationId());
        }else if (method_exists($this, 'getStripeServiceIntegrationOwnerId') && method_exists($this,'getStripeServiceIntegrationOwnerType')){
            $query = $query->where('owner_type', $this->getStripeServiceIntegrationOwnerType())->where('owner_id', $this->getStripeServiceIntegrationOwnerId());
        }else{
            $query = $query->where('owner_type', 'not-exists-expecting-null');
        }        

        return $query;
    }

    /**
     * Get the related stripe account where the user belongs to
     * 
     * Scoping by \App\Models\Subsidiary\Subsidiary
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return object|null
     */
    public function getStripeServiceIntegration($serviceIntegrationId = null)
    {
        if ($this->stripeServiceIntegrationRecentlyFetched !== null) {
            return $this->stripeServiceIntegrationRecentlyFetched;
        }
        
        $query = $this->getStripeServiceIntegrationQuery($serviceIntegrationId);

        if (is_null($query)) {
            return ;
        }

        $service = $query->first();
        
        if (is_null($service)) {
            return ;
        }

        $payloadColumn = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');

        if (isset($service->{$payloadColumn})) {
            $service->{$payloadColumn.'_decoded'} = json_decode($service->{$payloadColumn}, true);
        }

        return $this->stripeServiceIntegrationRecentlyFetched = $service;
    }

    /**
     * Determine if the customer has a Stripe customer ID and throw an exception if not.
     *
     * @return void
     *
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function assertStripeServiceIntegrationExists($serviceIntegrationId = null)
    {
        $query = $this->getStripeServiceIntegrationQuery($serviceIntegrationId);

        if (is_null($query)) {
            throw InvalidStripeServiceIntegration::notYetCreated($this);            
        }        

        $service = $query->first();
        
        if (is_null($service)) {
            throw InvalidStripeServiceIntegration::notYetCreated($this);
        }
    }

    /**
     * Get the related stripe secret key
     * 
     * @param int|null 
     * @return string|null
     */
    public function getRelatedStripeSecretKey($serviceIntegrationId = null)
    {
        $payloadColumn     = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');
        $stripeSecret      = config('stripe-multiple-accounts.stripe_integrations.payload.stripe_secret', 'stripe_secret');
        $stripeIntegration = $this->getStripeServiceIntegration($serviceIntegrationId);

        if (is_null($stripeIntegration)) {
            return ;
        }

        if (!isset($stripeIntegration->{$payloadColumn})) {
            return ;
        }

        return json_decode($stripeIntegration->{$payloadColumn}, true)[$stripeSecret];
    }

    /**
     * Get the related stripe key
     * 
     * @param int|null
     * @return string|null
     */
    public function getRelatedStripePublicKey($serviceIntegrationId = null)
    {
        $payloadColumn    = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');
        $stripePublicKey  = config('stripe-multiple-accounts.stripe_integrations.payload.stripe_key', 'stripe_key');
        $stripeIntegration = $this->getStripeServiceIntegration($serviceIntegrationId);

        if (is_null($stripeIntegration)) {
            return ;
        }

        if (!isset($stripeIntegration->{$payloadColumn})) {
            return ;
        }

        return json_decode($stripeIntegration->{$payloadColumn}, true)[$stripePublicKey];
    }
}
