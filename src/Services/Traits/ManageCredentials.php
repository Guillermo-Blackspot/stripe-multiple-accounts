<?php 

namespace BlackSpot\StripeMultipleAccounts\Services\Traits;

use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use Stripe\StripeClient;

/**
 * Manages the auth credentials to connect with stripe
 * 
 * @method getClient()
 * @method getSecretKey()
 * @method getPublicKey()
 */
trait ManageCredentials
{
    /**
     * Get the stripe client connection
     * 
     * @return \Stripe\StripeClient
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function getClient()
    {
        return new StripeClient($this->getSecretKey());
    }

    /**
     * Get the related stripe secret key
     * 
     * @return string
     * @throws BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function getSecretKey()
    {
        $this->assertServiceExists();

        $service       = $this->getService();
        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($service->{$payloadColumn})) {
            throw InvalidStripeServiceIntegration::payloadColumnNotFound($service, $payloadColumn);
        }

        $decoded = $service->{$payloadColumn.'_decoded'};

        if (! isset($decoded['stripe_secret'])) {
            throw InvalidStripeServiceIntegration::payloadAttributeValueIsNull($service, 'stripe_secret');
        }

        return $decoded['stripe_secret'];
    }

    /**
     * Get the related stripe key
     * 
     * @return string|null
     * @throws BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration
     */
    public function getPublicKey()
    {
        $this->assertServiceExists();

        $service       = $this->getService();
        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($service->{$payloadColumn})) {
            throw InvalidStripeServiceIntegration::payloadColumnNotFound($service, $payloadColumn);
        }

        $decoded = $service->{$payloadColumn.'_decoded'};

        if (! isset($decoded['stripe_key'])) {
            throw InvalidStripeServiceIntegration::payloadAttributeValueIsNull($service, 'stripe_key');
        }

        return $decoded['stripe_key'];    
    }
}
