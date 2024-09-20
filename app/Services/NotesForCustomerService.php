<?php

namespace App\Services;

use App\Models\NotesForCustomer;
use Config;
use Illuminate\Support\Facades\Auth;

class NotesForCustomerService extends BaseService
{
    protected $emailActivityService;
    protected $emailStatusService;
    protected $customerService;
    protected $sendEmailCustomerService;

    public function __construct(
        EmailActivityService $emailActivityService,
        EmailStatusService $emailStatusService,
        CustomerService $customerService,
        SendEmailCustomerService $sendEmailCustomerService
    ) {
        $this->emailActivityService = $emailActivityService;
        $this->emailStatusService = $emailStatusService;
        $this->customerService = $customerService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function getNotesForCustomer($quoteTypeId, $quoteId)
    {
        return NotesForCustomer::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
            ->orderBy('updated_at', 'desc')
            ->with('createdby:id,name')
            ->get();
    }

    public function addCustomerNote($request)
    {
        $newNote = new NotesForCustomer;
        $newNote->quote_type_id = $request->quote_type_id;
        $newNote->quote_id = $request->quote_id;
        $newNote->description = nl2br($request->description);
        $newNote->created_by_id = Auth::user()->id;
        $newNote->save();

        return $newNote->id;
    }

    public function notesSendToCustomer($request)
    {
        $ecomCarInsuranceQuoteUrl = Config::get('constants.ECOM_CAR_INSURANCE_QUOTE_URL');
        $emailTemplateId = (int) Config::get('constants.SIB_CAR_QUOTE_UPDATE_NOTES_TO_CUSTOMER_TEMPLATE');
        $customer = $this->customerService->getCustomerByEmail($request->customer_email);

        $emailData = (object) [
            'customerName' => $request->customer_name,
            'customerEmail' => $request->customer_email,
            'buttonUrl' => $ecomCarInsuranceQuoteUrl.$request->quote_uuid,
            'quoteCdbId' => $request->quote_cdb_id,
            'quoteTypeId' => $request->quote_type_id,
            'quoteId' => $request->quote_id,
            'notesForCustomer' => $request->description,
            'templateId' => $emailTemplateId,
            'customerId' => $customer->id,
        ];

        $response = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, 'car-quote-update-notes-to-customer');

        return $response;
    }
}
