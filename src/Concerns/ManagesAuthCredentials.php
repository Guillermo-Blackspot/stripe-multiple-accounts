<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegrationFinder;
use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use Illuminate\Support\Facades\DB;
use Stripe\Collection;
use Stripe\StripeClient;
/**
 * Manages the auth credentials to connect with stripe
 * 
 * @method getStripeServiceIntegrationQuery($serviceIntegrationId = null)
 * @method getStripeServiceIntegration($serviceIntegrationId = null)
 * @method assertStripeServiceExists($serviceIntegrationId = null)
 * @method getStripeClient($serviceIntegrationId = null)
 * @method getStripeSecretKey($serviceIntegrationId = null)
 * @method getStripePublicKey($serviceIntegrationId = null)
 */
trait ManagesAuthCredentials
{
    use ServiceIntegrationFinder;

    /**
     * Get the stripe service integration query
     * 
     * @param int|null $serviceIntegrationId
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getStripeServiceIntegrationQuery($serviceIntegrationId = null)
    {
        return $this->getServiceIntegrationQueryFinder($serviceIntegrationId, 'stripe', function (&$query) {
            $query->where([
                'name'       => ServiceIntegration::STRIPE_SERVICE,
                'short_name' => ServiceIntegration::STRIPE_SERVICE_SHORT_NAME,
            ]);
        });
    }

    /**
     * Get the related stripe account where the user belongs to
     * 
     * Scoping by \App\Models\Subsidiary\Subsidiary
     * 
     * @param int|null $serviceIntegrationId
     * @return ServiceIntegration
     * 
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|LogicException
     */
    public function getStripeServiceIntegration($serviceIntegrationId = null)
    {
        if ($this->serviceIntegrationWasLoaded($serviceIntegrationId)) {
            return $this->getServiceIntegrationLoaded($serviceIntegrationId);
        }

        $service = $this->resolveServiceIntegrationFromInstance($this, $serviceIntegrationId) ?? $this->getStripeServiceIntegrationQuery($serviceIntegrationId)->first();
        
        if ($service == null) {
            throw InvalidStripeServiceIntegration::notYetCreated($this);
        }

        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($service->{$payloadColumn})) {
            throw InvalidStripeServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }

        $payloadValue = $service->{$payloadColumn};

        $service->{$payloadColumn.'_decoded'} = is_array($payloadValue) ? $payloadValue : json_decode($payloadValue, true);

        return $this->putServiceIntegrationFound($service);
    }

    /**
     * Get the stripe client connection
     * 
     * @param int|null $serviceIntegrationId
     * @return \Stripe\StripeClient|null
     * 
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function getStripeClient($serviceIntegrationId = null)
    {
        $stripeSecretKey = $this->getStripeSecretKey($serviceIntegrationId);

        if ($stripeSecretKey == null) {
            return ;
        }

        return new StripeClient($stripeSecretKey);
    }

    /**
     * Get the related stripe secret key
     * 
     * @param int|null 
     * @return string
     *
     * @throws BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function getStripeSecretKey($serviceIntegrationId = null)
    {
        $stripeIntegration  = $this->getStripeServiceIntegration($serviceIntegrationId);
        $stripeSecretColumn = 'stripe_secret';
        $payloadColumn      = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($stripeIntegration->{$payloadColumn})) {
            throw InvalidStripeServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }

        $decoded = $stripeIntegration->{$payloadColumn.'_decoded'};

        if (! isset($decoded[$stripeSecretColumn])) {
            throw InvalidStripeServiceIntegration::payloadAttributeValueIsNull($this, $stripeSecretColumn);
        }        
        
        return $decoded[$stripeSecretColumn];
    }

    /**
     * Get the related stripe key
     * 
     * @param int|null
     * @return string|null
     * 
     * @throws BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function getStripePublicKey($serviceIntegrationId = null)
    {
        $stripeIntegration     = $this->getStripeServiceIntegration($serviceIntegrationId);
        $stripePublicKeyColumn = 'stripe_key';
        $payloadColumn         = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($stripeIntegration->{$payloadColumn})) {
            throw InvalidStripeServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }
        
        $decoded = $stripeIntegration->{$payloadColumn.'_decoded'};

        if (! isset($decoded[$stripePublicKeyColumn])) {
            throw InvalidStripeServiceIntegration::payloadAttributeValueIsNull($this, $stripePublicKeyColumn);
        }        
        
        return $decoded[$stripePublicKeyColumn];    
    }

    /**
     * Determine if the customer has a Stripe customer ID and throw an exception if not.
     *
     * @return void
     *
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function assertStripeServiceExists($serviceIntegrationId = null)
    {
        $this->getStripeServiceIntegration($serviceIntegrationId);
    }


    /**
     * Determine if the customer has a active Stripe service integration, throw an exception if not.
     *
     * @return void
     *
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function assertActiveStripeServiceExists($serviceIntegrationId = null)
    {
        if ($this->getStripeServiceIntegration($serviceIntegrationId)->disabled()) {
            InvalidStripeServiceIntegration::isDisabled($this);
        }
    }
}
