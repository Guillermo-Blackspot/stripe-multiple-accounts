<?php

namespace BlackSpot\StripeMultipleAccounts\Exceptions;

use Exception;

class InvalidStripeServiceIntegration extends Exception
{
    /**
     * Create a new InvalidCustomer instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @return static
     */
    public static function notYetCreated($owner)
    {
        return new static(class_basename($owner).' is not a Stripe Service Integration created yet.');
    }
}
