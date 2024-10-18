<?php

namespace App\Exports;

use App\Enums\EmbeddedProductEnum;
use App\Models\EmbeddedProduct;
use App\Repositories\EmbeddedProductRepository;
use App\Strategies\EmbeddedProducts\AlfredProtect;
use App\Strategies\EmbeddedProducts\EmbeddedProduct as EmbeddedProductStrategy;
use App\Strategies\EmbeddedProducts\TravelAnnual;
use App\Traits\ExcelExportable;

class EmbeddedProductReport
{
    use ExcelExportable;

    private $embeddedProduct;
    private $filters;
    private $epStrategy = null;

    public function __construct(EmbeddedProduct $embeddedProduct, $filters)
    {
        $this->embeddedProduct = $embeddedProduct;
        $this->filters = $filters;
        $isTravel = $embeddedProduct->short_code === EmbeddedProductEnum::TRAVEL;
        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($this->embeddedProduct->short_code);
        if ($isTravel) {
            $this->epStrategy = new TravelAnnual;
        } elseif ($isAlfredProtect) {
            $this->epStrategy = new AlfredProtect;
        } else {
            $this->epStrategy = new EmbeddedProductStrategy;
        }
    }

    /**
     * @return Illuminate\Support\Collection
     */
    public function collection()
    {
        $this->filters['excel_export'] = true;

        return EmbeddedProductRepository::getSoldTransactionList($this->embeddedProduct, $this->filters);
    }

    public function headings(): array
    {
        return $this->epStrategy->getExcelColumns();
    }

    public function map($certificate): array
    {
        return $this->epStrategy->getExcelData($certificate);
    }
}
