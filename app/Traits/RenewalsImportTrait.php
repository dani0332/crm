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

            if ($row[$column['index']] == '') {
                $quoteData[$key] = null;

                continue;
            }

            if (! empty($column['type']) && $column['type'] == 'date') {
                if (strpos($row[$column['index']], '/')) {
                    $quoteData[$key] = Carbon::createFromFormat('d/m/Y', $row[$column['index']])->format('d/m/Y');
                } else {
                    $quoteData[$key] = Carbon::instance(Date::excelToDateTimeObject((float) $row[$column['index']]))->format('d/m/Y');
                }
            } else {
                $quoteData[$key] = $row[$column['index']];
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
            if (strpos($value, '/')) {
                Carbon::createFromFormat('d/m/Y', $value)->format('d/m/Y');
            } else {
                $date = Date::excelToDateTimeObject((float) $value);
                Carbon::instance($date)->format('d/m/Y');
            }

            return true;
        } catch (\Exception $exception) {
            info('Date Issue value: '.$value.' Error: '.$exception->getMessage());

            return false;
        }
    }
}
