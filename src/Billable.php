<?php

namespace BlackSpot\StripeMultipleAccounts;

use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesCustomer;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesPaymentMethods;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesPaymentMethodSources;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesSubscriptions;
use BlackSpot\StripeMultipleAccounts\Concerns\PerformsCharges;
use BlackSpot\StripeMultipleAccounts\Relationships\HasStripeSubscriptions;
use BlackSpot\StripeMultipleAccounts\Relationships\HasStripeUsers;

trait Billable
{
    use ManagesAuthCredentials;
    use HasStripeUsers;
    use HasStripeSubscriptions;
    use ManagesCustomer;
    use ManagesPaymentMethodSources;
    use ManagesPaymentMethods;
    use PerformsCharges;
    use ManagesSubscriptions;

    public function clearStripeBillableCache()
    {
        $this->stripeServiceIntegrationRecentlyFetched = null;
        $this->stripeCustomerRecentlyFetched           = null;
        $this->stripeCustomerIdRecentlyFetched         = null;
    }
}