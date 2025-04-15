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
}
