<?php

namespace App\Services;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UploadResourceService extends BaseService
{

    public function createResource( $request )
	{
		$data        = $request->all();
		$timestamp   = Carbon::now()->timestamp;
		$fileName    = $timestamp . '_' . str_replace(' ', '',$request->file( 'file' )->getClientOriginalName());
        $filePath = $request->file('file')->storeAs('/', $fileName, 'azure');
		return $filePath;
	}

}
