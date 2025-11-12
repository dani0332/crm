<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\PermissionsEnum;

class SmartPhoneQuoteController extends Controller
{
    public function __construct(
        public SmartPhoneQuoteService $smartPhoneQuoteService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_LIST.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['index']]);
        $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_CREATE, ['only' => ['create', 'store']]);
        $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_EDIT.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_SHOW.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['show']]);
    }
    public function index()
    {

        return inertia('SmartPhoneQuote/Index');
    }

}
