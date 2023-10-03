<?php

namespace BlackSpot\SystemCharges\Exceptions;

use Exception;

class InvalidStripeSubscription extends Exception
{    
    public static function unknownInterval($owner, $interval)
    {
        return new static(class_basename($owner)." Unknown interval \"{$interval}\", choose one of [day,week,month or year]");
    }    

    public static function unknownIntervalFromFirstItem($owner)
    {
        return new static(class_basename($owner)." Unknown interval, from  \$firstItem->subscription_settings['interval'] property not found, choose one of [day,week,month or year]");
    }    

    public static function pastDate($owner, $date)
    {
        return new static(class_basename($owner)." Can not create subscriptions with a past date ({$date->format('d/m/Y H:i')}).");
    }    

    public static function emptyItems($owner)
    {
        return new static(class_basename($owner)." At least one price is required when starting subscriptions.");
    } 
    
}
