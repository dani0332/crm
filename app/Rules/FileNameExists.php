<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FileNameExists implements Rule
{
    protected $table;
    protected $column;

    /**
     * Create a new rule instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return void
     */
    public function __construct($table, $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ! DB::table($this->table)->where($this->column, $value->getClientOriginalName())->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This file has already been uploaded. Please upload a new file with a unique file name.';
    }
}
