<?php

namespace App\Services;

use App\Models\QuoteStatus;
use DB;
use Illuminate\Http\Request;

class LeadStatusService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('quote_status as ls')
            ->select('ls.id', 'ls.uuid', 'ls.text', 'ls.created_at', 'ls.updated_at');
    }

    public function saveLeadStatus(Request $request)
    {
        $existingStatus = QuoteStatus::where('text', $request->text)->first();
        if ($existingStatus != null) {
            return 'Error: name already exists';
        }
        $leadStatus = new QuoteStatus;
        $leadStatus->code = $request->text;
        $leadStatus->text = $request->text;
        $leadStatus->is_active = true;
        $leadStatus->save();

        return QuoteStatus::find($leadStatus->id);
    }

    public function updateLeadStatus(Request $request, $id)
    {
        $quoteStatus = QuoteStatus::where('id', $id)->first();
        $quoteStatus->text = $request->text;
        $quoteStatus->save();

        if (isset($request->return_to_view)) {
            return redirect('quote/leadstatus/'.$id)->with('success', 'Lead Status has been updated');
        }
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (! empty($request[$item])) {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else {
                        $this->query->where('ls.'.$item, $request[$item]);
                    }
                }
            }
        }

        return $this->query->orderBy('ls.created_at', 'DESC');
    }

    public function getEntity($id)
    {
        return $this->query->where('ls.id', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return QuoteStatus::where('id', $id)->first();
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'text' => 'input|text|required|title',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'text':
                $title = 'Status Name';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => '',
            'list' => '',
            'update' => '',
            'show' => '',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['text'];
    }
}
