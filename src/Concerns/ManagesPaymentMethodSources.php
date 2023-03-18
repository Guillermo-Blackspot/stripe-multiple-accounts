<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

/**
 * @deprecated legacy
 */
trait ManagesPaymentMethodSources
{
    /**
     * Add stripe payment method source
     *  
     * Send to stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param string|null $tokenId
     * @param array $opts
     * @deprecated 
     * @return \Stripe\BankAccount|\Stripe\Card|\Stripe\Source|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function addStripePaymentMethodSource($serviceIntegrationId = null, $tokenId, array $opts = [])
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->addStripePaymentMethodSource($tokenId, $opts);
    }

    /**
     * Detach payment method source from stripe customer payment methods
     * 
     * Send to stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param string $sourceId
     * @param array $opts
     * @deprecated
     * @return \Stripe\PaymentMethod|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function deleteStripePaymentMethodSource($serviceIntegrationId = null, $sourceId, array $opts = [])
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->deleteStripePaymentMethodSource($sourceId, $opts);    
    }
}
