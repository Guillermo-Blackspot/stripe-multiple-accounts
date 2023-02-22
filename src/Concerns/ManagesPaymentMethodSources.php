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
     * @throws \Stripe\Exception\ApiErrorException
     * @return \Stripe\BankAccount|\Stripe\Card|\Stripe\Source|null
     */
    public function addStripePaymentMethodSource($serviceIntegrationId = null, $tokenId, $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $stripeCustomerId = $this->getStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return null;
        }

        $opts['source'] = $tokenId;
    
        return $stripeClientConnection->customers->createSource($stripeCustomerId, $opts);
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
     */
    public function deleteStripePaymentMethodSource($serviceIntegrationId = null, $sourceId, $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $stripeCustomerId = $this->getStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return null;
        }

        return $stripeClientConnection->customers->deleteSource($stripeCustomerId, $sourceId, null, $opts);
    }
}
