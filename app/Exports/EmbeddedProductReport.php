<?php

namespace App\Exports;

use App\Models\EmbeddedProduct;
use App\Repositories\EmbeddedProductRepository;
use App\Strategies\EmbeddedProducts\AlfredProtect;
use App\Strategies\EmbeddedProducts\EmbeddedProduct as EmbeddedProductStrategy;
use App\Traits\ExcelExportable;

class EmbeddedProductReport
{
    use ExcelExportable;

    private $embeddedProduct;
    private $filters;

    public function __construct(EmbeddedProduct $embeddedProduct, $filters)
    {
        $this->embeddedProduct = $embeddedProduct;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $this->filters['excel_export'] = true;

        return EmbeddedProductRepository::getSoldTransactionList($this->embeddedProduct, $this->filters);
    }

    public function headings(): array
    {
        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($this->embeddedProduct->short_code);
        $epStrategy = null;
        if ($isAlfredProtect) {
            $epStrategy = new AlfredProtect();
        } else {
            $epStrategy = new EmbeddedProductStrategy();
        }

        return $epStrategy->getExcelColumns();
    }

    public function map($certificate): array
    {
        $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($this->embeddedProduct->short_code);
        $epStrategy = null;
        if ($isAlfredProtect) {
            $epStrategy = new AlfredProtect();
        } else {
            $epStrategy = new EmbeddedProductStrategy();
        }

        return $epStrategy->getExcelData($certificate);
    }
}
