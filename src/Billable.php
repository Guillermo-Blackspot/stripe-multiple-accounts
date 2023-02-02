<?php

namespace BlackSpot\StripeMultipleAccounts;

use BlackSpot\StripeMultipleAccounts\Concerns\HandlesServiceIntegrations;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesCustomerInformation;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesPaymentMethods;
use BlackSpot\StripeMultipleAccounts\Relationships\HasServiceIntegrationUsers;

trait Billable
{
    use HasServiceIntegrationUsers;
    use HandlesServiceIntegrations;
    use ManagesAuthCredentials;
    use ManagesCustomerInformation;
    use ManagesPaymentMethods;


    public function clearStripeBillableCache()
    {
        $this->stripeServiceIntegrationRecentlyFetched = null;
        $this->stripeCustomerRecentlyFetched           = null;
        $this->stripeCustomerIdRecentlyFetched         = null;
    }
}