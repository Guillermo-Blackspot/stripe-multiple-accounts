<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

/**
 * @deprecated
 */
trait ManagesPaymentMethodSources
{
    /**
     * Add stripe payment method source
     *  
     * Send to stripe
     * 
     * @param int|string|null $serviceIntegrationId or you can pass directly the $tokenId instead it
     * @param string|null $tokenId
     * @param array $opts
     * @deprecated 
     * @throws \Stripe\Exception\ApiErrorException
     * @return \Stripe\BankAccount|\Stripe\Card|\Stripe\Source|null
     */
    public function addStripeCustomerPaymentMethodSource($serviceIntegrationId = null, $tokenId = null, $opts = [])
    {
        if (!is_numeric($serviceIntegrationId)) {
            $tokenId = $serviceIntegrationId;
            $serviceIntegrationId = null;
        }

        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

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
     * @param int|null $serviceIntegrationId or you can pass directly the $paymentMethodId instead it
     * @param string|null $sourceId
     * @param array $opts
     * @deprecated
     * @return \Stripe\PaymentMethod|null
     */
    public function deleteStripeCustomerPaymentMethodSource($serviceIntegrationId = null, $sourceId = null, $opts = [])
    {
        if (!is_numeric($serviceIntegrationId)) {
            $sourceId = $serviceIntegrationId;
            $serviceIntegrationId = null;
        }

        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return null;
        }

        return $stripeClientConnection->customers->deleteSource($stripeCustomerId, $sourceId, null, $opts);
    }
}
