<?php

namespace BlackSpot\StripeMultipleAccounts;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use DateTimeInterface;

class SubscriptionUtils
{
    /**
     * Resolve billingCycleAnchor and if is null take the current time
     *
     * @param null|\DateTimeInterface|string $billingCycleAnchor
     * @return \DateTimeInterface
     */
    public static function resolveBillingCycleAnchor($billingCycleAnchor = null)
    {
        if (is_null($billingCycleAnchor)) {
            return Date::now();
        }

        if (! ($billingCycleAnchor instanceof DateTimeInterface)) {            
            $billingCycleAnchor = Date::parse($billingCycleAnchor);
        }

        if ($billingCycleAnchor->isPast() && ! $billingCycleAnchor->isToday()) {            
            $billingCycleAnchor = Date::now();
        }

        return $billingCycleAnchor;
    }

    /**
     * Convert the input subscription items to collection
     * 
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database|Eloquent\Collection
     * 
     * @return \Illuminate\Support\Collection|\Illuminate\Database|Eloquent\Collection
     */
    public static function subscriptionItemsToCollection($items)
    {
        if ($items === null) {
            return Collection::make([]);
        }

        if ($items instanceof EloquentModel) {
            $items = Collection::make([$items]);
        } else if (is_array($items)) {
            $items = Collection::make($items);
        }

        return $items;
    }

    /**
     * Calculate the cancel at
     *
     * @param string  $interval
     * @param int  $intervalCount  - default 1
     * @param int|null  $expectedInvoices  - null is never will be canceled
     * @param string|null|DateTimeInterface  $billingCycleAnchor  -- null is now
     * @return \DateTimeInterface|null
     */
    public static function calculateCancelAtFromExpectedInvoices($interval, $intervalCount = 1, $expectedInvoices, $billingCycleAnchor = null)
    {   
        if ($expectedInvoices === null) {
            return null;  // never will be canceled, only when accumulates two pending invoices
        }

        $billingCycleAnchor = static::resolveBillingCycleAnchor($billingCycleAnchor)->copy();
        $carbonFunction     = 'add'.(ucfirst($interval)).'s';  // addDays, addWeeks, addMonths, addYears

        if (! $intervalCount) {
            $intervalCount = 1;
        }

        $cancelAt = $billingCycleAnchor;
        
        for ($i = 0; $i < ($expectedInvoices + 1); $i++) { 
            $cancelAt = $cancelAt->{$carbonFunction}($intervalCount); // every $intervalCount(2) months or any interval
        }

        return $cancelAt;
    }

    /**
     * Calculate the next invoice date
     * 
     * @param string  $interval
     * @param int  $intervalCount  - default 1
     * @param string|null|DateTimeInterface  $billingCycleAnchor  -- null is now
     * 
     * @return \DateTimeInterface
     */
    public static function calculateNextInvoice($interval, $intervalCount, $billingCycleAnchor)
    {
        $billingCycleAnchorCopy = static::resolveBillingCycleAnchor($billingCycleAnchor);

        return static::calculateNextEndPeriod($interval, $intervalCount, $billingCycleAnchorCopy);
    }

    /**
     * Calculate the next period from the given date
     * 
     * @param string  $interval
     * @param int  $intervalCount  - default 1
     * @param string|null|DateTimeInterface  $renewDate  -- null is now
     *
     * @return \DateTimeInterface
     */
    public static function calculateNextEndPeriod($interval, $intervalCount, $renewDate = null)
    {
        if ($renewDate && ! ($renewDate instanceof DateTimeInterface)) {
            $nextPeriod = Date::parse($renewDate)->copy();
        }else{
            $nextPeriod = Date::now();
        }

        $carbonFunction = 'add'.(ucfirst($interval)).'s';  // addDays, addWeeks, addMonths, addYears

        // Automatically add one day taking as reference the last day of the current period
        //$nextPeriod = $nextPeriod->addDay(1);

        return $nextPeriod->{$carbonFunction}($intervalCount ?? 1);        
    }
}
