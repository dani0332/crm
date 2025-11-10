<?php

namespace App\Traits;

use App\Models\CustomerVerificationDetail;
use App\Models\RegistrationCertificate;
use App\Models\VehicleDriverDetail;

trait OCRTrait
{
    private function getCustomerVerificationDetailCount(int $quotableId, string $quotableType): int
    {
        return CustomerVerificationDetail::where('quotable_type', $quotableType)
            ->where('quotable_id', $quotableId)
            ->count();
    }

    private function getVehicleDriverDetailCount(int $quoteableId, string $quoteableType): int
    {
        return VehicleDriverDetail::where('quoteable_type', $quoteableType)
            ->where('quoteable_id', $quoteableId)
            ->count();
    }

    private function getRegistrationCertificateCount(int $certificatableId, string $certificatableType): int
    {
        return RegistrationCertificate::where('certificatable_type', $certificatableType)
            ->where('certificatable_id', $certificatableId)
            ->count();
    }

    public function hasOCRData(int $quoteId, string $quoteType): bool
    {
        return $this->getCustomerVerificationDetailCount($quoteId, $quoteType) > 0
            && $this->getVehicleDriverDetailCount($quoteId, $quoteType) > 0
            && $this->getRegistrationCertificateCount($quoteId, $quoteType) > 0;
    }
}
