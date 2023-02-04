<?php

namespace BlackSpot\StripeMultipleAccounts;

use BlackSpot\StripeMultipleAccounts\Concerns\HandlesServiceIntegrations;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesCustomerInformation;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesPaymentMethods;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesPaymentMethodSources;
use BlackSpot\StripeMultipleAccounts\Concerns\PerformsCharges;
use BlackSpot\StripeMultipleAccounts\Relationships\HasServiceIntegrationSubscriptions;
use BlackSpot\StripeMultipleAccounts\Relationships\HasServiceIntegrationUsers;

trait Billable
{
    use HasServiceIntegrationUsers;
    use HasServiceIntegrationSubscriptions;
    use HandlesServiceIntegrations;
    use ManagesAuthCredentials;
    use ManagesCustomerInformation;
    use ManagesPaymentMethodSources;
    use ManagesPaymentMethods;
    use PerformsCharges;

    public function clearStripeBillableCache()
    {
        $this->stripeServiceIntegrationRecentlyFetched = null;
        $this->stripeCustomerRecentlyFetched           = null;
        $this->stripeCustomerIdRecentlyFetched         = null;
    }
}