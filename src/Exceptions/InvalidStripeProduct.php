<?php

namespace BlackSpot\StripeMultipleAccounts\Exceptions;

use Exception;

class InvalidStripeProduct extends Exception
{
    /**
     * Create a new InvalidCustomer instance.
     *
     * @param string  $functionName
     * @param string  $properties
     * @return static
     * 
     */
    public static function missingRequiredProperties($functionName, $properties)
    {        
        return new static("Missing properties on {$functionName} for create new product [{$properties}]");
    }
}
