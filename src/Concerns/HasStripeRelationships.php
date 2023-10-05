<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Models\StripeCustomer;
use BlackSpot\StripeMultipleAccounts\Services\StripeService;

trait HasStripeRelationships
{    
    protected $stripeServiceInstance;
  
    public function getStripeAttribute()
    {
        if ($this->stripeServiceInstance !== null) {
            return $this->stripeServiceInstance;
        }

        return $this->stripeServiceInstance = new StripeService($this);
    }

    public function stripe_customers()
    {
        return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.customer', StripeCustomer::class), 'service_integration_id');
    }
}