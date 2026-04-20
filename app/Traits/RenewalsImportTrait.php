<?php

namespace App\Traits;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

trait RenewalsImportTrait
{
    /**
     * map row with keys.
     *
     * @return array
     */
    public function mapQuoteData($row)
    {
        $columns = $this->getColumns();

        $quoteData = [];
        foreach ($columns as $key => $column) {
            $index = $column['index'];
            if (! array_key_exists($index, $row) || $this->isBlankImportCell($row[$index])) {
                $quoteData[$key] = null;

                continue;
            }

            $value = $row[$index];
            if (! empty($column['type']) && $column['type'] == 'date') {
                $quoteData[$key] = $this->formatDate($value);
            } else {
                $quoteData[$key] = $value;
            }
        }

        return $quoteData;
    }

    /**
     * Whether a spreadsheet cell should map to null in quote data.
     *
     * Only genuine empty signals from the reader (null, '') are treated as blank.
     * Numeric 0/0.0 are preserved because several imports accept 0 as a legitimate
     * user-entered value (e.g. UploadAndUpdateImport::excess must be 0 for TPL
     * plans, and amount fields such as ancillary_excess/driver_cover_amount can
     * legitimately be 0). Treating those as blank collapses real data to null.
     */
    protected function isBlankImportCell(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * @return array
     */
    public function mapData($row)
    {
        $columns = $this->getColumns();

        $quoteData = [];
        foreach ($columns as $key => $column) {
            $quoteData[$key] = isset($row[$column['index']]) ? $row[$column['index']] : null;
        }

        return $quoteData;
    }

    /**
     * Attributes Mapping, pluck titles from columns and these will be used in validation as field name.
     *
     * @return string[] e.g 0 => Customer Name, 1 => Customer Email
     */
    public function customValidationAttributes()
    {
        $colums = collect($this->getColumns());

        return $colums->pluck('title', 'index')->toArray();
    }

    /**
     * @return array
     */
    public function getRules()
    {
        $rules = [];
        $columns = collect($this->getColumns())->pluck('rules', 'index');

        $columns->each(function ($item, $index) use (&$rules) {
            $rules['*.'.$index] = $item;
        });

        return $rules;
    }

    /**
     * @return bool
     */
    public function validateDate($value)
    {
        try {
            $this->formatDate($value);

            return true;
        } catch (\Exception $exception) {
            info('Date Issue value: '.$value.' Error: '.$exception->getMessage());

            return false;
        }
    }

    /**
     * Format a date value from the import row.
     *
     * @param  mixed  $value
     */
    protected function formatDate($value): string
    {
        $format = config('constants.DATE_DISPLAY_SLASH_FORMAT');

        if (strpos((string) $value, '/')) {
            return Carbon::createFromFormat($format, $value)->format($format);
        }

        return Carbon::instance(Date::excelToDateTimeObject((float) $value))->format($format);
    }
}
