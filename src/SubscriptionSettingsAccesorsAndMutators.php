<?php

namespace BlackSpot\StripeMultipleAccounts;

/**
 * The common accesors for the subscription settings
 * 
 * By default is on the "es" language
 * 
 * @property string $readable_subscription_billing_charges
 * @property string $readable_subscription_interval
 * @property string $readable_subscription_trial_days
 * @property string $readable_subscription_billing_cycle_anchor
 */
trait SubscriptionSettingsAccesorsAndMutators
{
    public function getReadableSubscriptionBillingChargesAttribute()
    {
        if ($this->payment_type != self::PAYMENT_TYPE_SUBSCRIPTION || !$this->subscription_settings || !isset($this->subscription_settings['interval']) || !isset($this->subscription_settings['interval_count'])) {
            return ;
        }

        $text = 'Cobrar cada ';
        
        if ($this->subscription_settings['interval'] == 'week') {
            $text .= (int) $this->subscription_settings['interval_count'] > 1 ? '--replace semanas' : 'semana';
        }else if ($this->subscription_settings['interval'] == 'month') {                    
            $text .= (int) $this->subscription_settings['interval_count'] > 1 ? '--replace meses' : 'mes';
        }else if ($this->subscription_settings['interval'] == 'year') {
            $text .= (int) $this->subscription_settings['interval_count'] > 1 ? '--replace años' : 'año';
        }
        
        return str_replace('--replace', ((int) $this->subscription_settings['interval_count'] ?? ''), $text);
    }

    public function getReadableSubscriptionIntervalAttribute()
    {
        if ($this->payment_type != self::PAYMENT_TYPE_SUBSCRIPTION || !$this->subscription_settings || !isset($this->subscription_settings['interval'])) {
            return ;
        }

        if ($this->subscription_settings['interval'] == 'month') {
            return 'Mensual';
        }elseif ($this->subscription_settings['interval'] == 'year') {
            return 'Anual';
        }elseif ($this->subscription_settings['interval'] == 'week') {
            return 'Semanal';
        }else{
            return ;
        }
    }

    public function getReadableSubscriptionTrialDaysAttribute()
    {
        if ($this->payment_type != self::PAYMENT_TYPE_SUBSCRIPTION || !$this->subscription_settings) {
            return ;
        }

        if (!isset($this->subscription_settings['trial_days'])) {
            return '0 días de prueba';
        }

        return (int) $this->subscription_settings['trial_days'] . ' días de prueba';
    }

    public function getReadableSubscriptionBillingCycleAnchorAttribute()
    {
        if ($this->payment_type != self::PAYMENT_TYPE_SUBSCRIPTION || !$this->subscription_settings) {
            return ;
        }

        if (!isset($this->subscription_settings['billing_cycle_anchor'])) {
            return 'En la fecha que se creé la suscripción';
        }

        return 'Empezando en la fecha '. carbon($this->subscription_settings['billing_cycle_anchor'])->format('d-m-Y h:i a');
    } 
}
