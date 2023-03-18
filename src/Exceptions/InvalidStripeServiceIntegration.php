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
        return new static(class_basename($owner).' has not a Stripe Service Integration related yet.');
    }

    public static function isDisabled($owner)
    {
        return new static(class_basename($owner).' has a Stripe Service Integration disabled.');
    }

    public static function payloadColumnNotFound($owner, $property)
    {
        return new static(class_basename($owner).' the table column "'.$property.'" not found.');
    }

    public static function payloadAttributeValueIsNull($owner, $payloadProperty)
    {
        return new static(class_basename($owner).' the payload property "'.$payloadProperty.'" not found or is null.');
    }    
}
