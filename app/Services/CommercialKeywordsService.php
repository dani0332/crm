<?php

namespace App\Services;

use App\Models\CommercialKeyword;
use Illuminate\Http\RedirectResponse;

class CommercialKeywordsService extends BaseService
{
    /**
     * store new commercial keyword function
     */
    public function store(array $attributes): RedirectResponse
    {
        $latestRecord = CommercialKeyword::select('id')->orderByDesc('id')->first();

        $keyword = CommercialKeyword::create([
            'id' => ($latestRecord->id + 1),
            'name' => $attributes['name'],
            'key' => strtoupper(str_replace(' ', '_', $attributes['name'])),
        ]);

        return redirect()->route('admin.commercial.keywords.show', ($latestRecord->id + 1))->with('success', 'Commercial Keyword has been stored');
    }

    /**
     * update a keyword function
     */
    public function update($id, array $attributes): RedirectResponse
    {
        $keyword = CommercialKeyword::find($id);
        if ($keyword) {
            $keyword->name = $attributes['name'];
            $keyword->key = strtoupper(str_replace(' ', '_', $attributes['name']));
            $keyword->update();
        } else {
            return redirect()->route('admin.commercial.keywords')->with('message', 'Record not found');
        }

        return redirect()->route('admin.commercial.keywords.show', ($id))->with('success', 'Commercial Keyword has been updated');
    }
}
