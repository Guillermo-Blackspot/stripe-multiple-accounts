<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

trait MorphToOwner
{
    /**
     * Get the owner of the stripe customer
     *
     * @return void
     */
    public function owner()
    {
        return $this->morphTo('owner');
    }    
}
