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

        return $this->stripeServiceIntegrationRecentlyFetched = ($serviceIntegrationId == null)
                                                                    ? $this->getStripeServiceIntegration()
                                                                    : DB::table(config('stripe-multiple-accounts.service_integrations.table')) 
                                                                        ->where(config('stripe-multiple-accounts.service_integrations.primary_key'), $serviceIntegrationId)
                                                                        ->first();
    }

    /**
     * Get the related stripe account where the user belongs to
     * 
     * Scoping by \App\Models\Subsidiary\Subsidiary
     * 
     * @return object|null
     */
    public function getStripeServiceIntegration()
    {
        if ($this->getStripeServiceIntegrationMorphId() == null) {
            return null;
        }

        return DB::table(config('stripe-multiple-accounts.stripe_integrations.table'))
                    ->where('owner_type', $this->getStripeServiceIntegrationMorphType())
                    ->where('owner_id', $this->getStripeServiceIntegrationMorphId())
                    ->where('name', 'Stripe')
                    ->where('short_name', 'str')
                    ->first();
    }
}
