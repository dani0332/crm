<?php

namespace App\Services;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadResourceService extends BaseService
{

    public function createResource( $request )
	{
		$data        = $request->all();
		$timestamp   = Carbon::now()->timestamp . "-". Str::random(10);
		$fileName    = $timestamp . '_' . str_replace(' ', '',$request->file( 'file' )->getClientOriginalName());
        $filePath = $request->file('file')->storeAs('/', $fileName, 'azure');
		return $filePath;
	}

}
