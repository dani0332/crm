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
            if (! array_key_exists($index, $row) || $row[$index] === '' || $row[$index] === null) {
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
