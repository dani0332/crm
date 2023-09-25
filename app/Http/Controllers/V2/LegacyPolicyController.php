<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Repositories\InslyDetailRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegacyPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $policies = [];
        if (! empty($request->all())) {
            $policies = InslyDetailRepository::getData();
        }

        return inertia('LegacyPolicy/Index', ['policies' => $policies]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($mongoId)
    {

        //$expiryDate = now()->addDay(); //The link will be expire after 1
        //$url = 'afia/2020_09/21/10037772/31948184.png';
        //$temporaryUrl = Storage::disk('s3')->temporaryUrl($url, $expiryDate);

         //dd($temporaryUrl);

        // $disk = Storage::disk('s3');

        // $expiration = Carbon::now()->addMinutes(1440); // Change 1440 to the number of minutes you want
        // $url = $disk->getAwsTemporaryUrl($disk->getDriver()->getAdapter(), $value, $expiration, []);

        // dd($url);

        //  $s3 = Storage::disk('s3');
        // $file = 'afia/2020_09/21/10037772/31948184.png';
        // $s3_file = $s3->getDriver()->getAdapter()->getClient()->getObject([
        //     'Bucket' => env('AWS_BUCKET'),
        //     'Key' => $file
        // ]);
        // return response($s3_file['Body'])
        //     ->header('Content-Type', $s3_file['ContentType'])
        //     ->header('Content-Disposition', 'attachment; filename="' . $file . '"');

        // // $path = 'afia/2020_09/21/14094033/14094035.pdf';
        // $path = 'afia/2020_09/21/10037772/31948184.png';
        // // $s3 = Storage::disk('s3');
        // // $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinute());

        // $s3 = Storage::disk('s3');
        // $file = 'example_file.jpg';
        // $s3_file = $s3->getDriver()->getAdapter()->getClient()->getObject([
        //     'Bucket' => env('AWS_BUCKET'),
        //     'Key' => $file
        // ]);
        // return response($s3_file['Body'])
        //     ->header('Content-Type', $s3_file['ContentType'])
        //     ->header('Content-Disposition', 'attachment; filename="' . $file . '"');

        // $url =  $s3->url('demo');
        // $path = 'afia/2020_09/21/10037772/31948184.png';
        // $s3 = Storage::disk('s3');
        // $expiry = "+10 minutes";
        // $s3config = 's3';
        // $client = $s3->getDriver()->getAdapter()->getClient();
        // $command = $client->getCommand('GetObject', [
        //     'Bucket' => config("filesystems.disks.$s3config.bucket"),
        //     'Key'    => $path
        // ]);

        // $request = $client->createPresignedRequest($command, $expiry);
        // $url = (string)$request->getUri();

        // dd($url);
        // $s3 = Storage::disk('s3')->getAdapter()->getClient();
        // $url = $s3->getObjectUrl(env('AWS_BUCKET'), 'afia/2020_09/21/14094033/14094035.pdf');

        // dd($url);
        $policy = InslyDetailRepository::getBy('_id', $mongoId);

        // dd($policy->toArray());
        return inertia('LegacyPolicy/Show', ['policy' => $policy]);
    }

    public function moveToImcrm(Request $request)
    {

        $policy = InslyDetailRepository::saveToImcrm($request->toArray());

        return $policy;
    }

    public function getS3TempUrl(Request $request)
    {
        $expiryDate = now()->addMinutes(40);
        $fileName = $request->fileName;
        //$fileName = 'afia/2020_09/21/10037772/31948184.png';
        $temporaryUrl = null;    
        if (Storage::disk('s3')->has($fileName)) {
            $temporaryUrl = Storage::disk('s3')->temporaryUrl($fileName, $expiryDate);
        }    
        // Check if a temporary URL was generated
        if ($temporaryUrl) {
            return response()->json(['url' => $temporaryUrl]);
        } else {
            return response()->json(['error' => 'Failed to retrieve URL from the AWS server']);
        }
    }    
}
