<?php

namespace BlackSpot\StripeMultipleAccounts;

use BlackSpot\StripeMultipleAccounts\Concerns\HandlesServiceIntegrations;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesCustomerInformation;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesPaymentMethods;

trait Billable
{
    use HandlesServiceIntegrations;
    use ManagesAuthCredentials;
    use ManagesCustomerInformation;
    use ManagesPaymentMethods;
}