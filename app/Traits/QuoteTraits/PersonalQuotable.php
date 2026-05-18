<?php

namespace App\Traits\QuoteTraits;

use App\Enums\QuoteTypeId;

trait PersonalQuotable
{
    public function isBike()
    {
        return $this->quote_type_id === QuoteTypeId::Bike;
    }

    public function isHome()
    {
        return $this->quote_type_id === QuoteTypeId::Home;
    }

    public function isPet()
    {
        return $this->quote_type_id === QuoteTypeId::Pet;
    }

    public function isTravel()
    {
        return $this->quote_type_id === QuoteTypeId::Travel;
    }

    public function isJetski()
    {
        return $this->quote_type_id === QuoteTypeId::Jetski;
    }

    public function isCycle()
    {
        return $this->quote_type_id === QuoteTypeId::Cycle;
    }

    public function isYacht()
    {
        return $this->quote_type_id === QuoteTypeId::Yacht;
    }

    public function isLife()
    {
        return $this->quote_type_id === QuoteTypeId::Life;
    }
    public function isSavings()
    {
        return $this->quote_type_id === QuoteTypeId::Savings;
    }

    public function isDevice()
    {
        return $this->quote_type_id === QuoteTypeId::Device;
    }

    public function isCyber()
    {
        return $this->quote_type_id === QuoteTypeId::Cyber;
    }
}
