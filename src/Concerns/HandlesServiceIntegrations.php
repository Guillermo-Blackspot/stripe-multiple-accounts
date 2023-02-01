<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use Illuminate\Support\Facades\DB;
use Stripe\Collection;
use Stripe\StripeClient;

/**
 * Manages the auth credentials to connect with stripe
 * 
 * @method getRelatedStripeSecretKey($serviceIntegrationId = null)
 */
trait HandlesServiceIntegrations
{   
    private $stripeServiceIntegrationRecentlyFetched = null;
    
    abstract function getStripeServiceIntegrationMorphId();
    abstract function getStripeServiceIntegrationMorphType();
    
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
     * Get the service integrations of Stripe
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return object
     */
    public function resolveStripeServiceIntegration($serviceIntegrationId = null)
    {
        if ($this->stripeServiceIntegrationRecentlyFetched !== null) {
            return $this->stripeServiceIntegrationRecentlyFetched;
        }

        return $this->stripeServiceIntegrationRecentlyFetched = $this->getStripeServiceIntegration($serviceIntegrationId);
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
        $service = null;
        $query   = DB::table(config('stripe-multiple-accounts.stripe_integrations.table'))
                    ->where('name', 'Stripe')
                    ->where('short_name', 'str');

        if (!is_null($serviceIntegrationId)) {
            $service = $query->where(config('stripe-multiple-accounts.stripe_integrations.primary_key'), $serviceIntegrationId)->first();  
        }else if ($this->getStripeServiceIntegrationMorphId() != null){            
            $service = $query->where('owner_type', $this->getStripeServiceIntegrationMorphType())->where('owner_id', $this->getStripeServiceIntegrationMorphId())->first();
        }

        if (is_null($service)) {
            return ;
        }

        $payloadColumn = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');

        if (isset($service->{$payloadColumn})) {
            $service->{$payloadColumn.'_decoded'} = json_decode($service->{$payloadColumn}, true);
        }

        return $service;
    }
}
