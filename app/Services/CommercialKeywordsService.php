<?php

namespace App\Services;

use App\Models\CommercialKeyword;
use Illuminate\Http\RedirectResponse;

class CommercialKeywordsService extends BaseService
{

    /**
     * store new commercial keyword function
     *
     * @param array $attributes
     * @return RedirectResponse
     */
    public function store(array $attributes):RedirectResponse
    {
        $latestRecord = CommercialKeyword::select('id')->orderByDesc('id')->first();

        $keyword = new CommercialKeyword();
        $keyword->id = ($latestRecord->id + 1);
        $keyword->name = $attributes['name'];
        $keyword->key  = strtoupper(str_replace(' ', '_', $attributes['name']));
        $keyword->save();

        return redirect()->back()->with('success', 'Commercial Keyword has been stored');
    }

    /**
     * update a keyword function
     *
     * @param array $attributes
     * @return RedirectResponse
     */
    public function update($id, array $attributes):RedirectResponse
    {
        $keyword = CommercialKeyword::find($id);
        if($keyword){
            $keyword->name = $attributes['name'];
            $keyword->key  = strtoupper(str_replace(' ', '_', $attributes['name']));
            $keyword->update();
        } else {
            return redirect()->route('admin.commercial.keywords')->with('message', 'Record not found');
        }

        return redirect()->route('admin.commercial.keywords')->with('success', 'Commercial Keyword has been updated');
    }
}
