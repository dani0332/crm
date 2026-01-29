<?php

declare(strict_types=1);

use App\DTO\AutomationFailedEmailDataRequest;
use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Services\PolicyIssuanceAutomation\AutomationFailedService;

function setApplicationStorageValue(string $key, string $value): void
{
    $record = ApplicationStorage::where('key_name', $key)->first();
    if (! $record) {
        $record = new ApplicationStorage();
        $record->key_name = $key;
    }

    $record->value = $value;
    $record->save();
}
it('normalizes device cc emails and includes advisor when booking fails', function () {
    config(['constants.APP_ENV' => EnvEnum::PRODUCTION]);

    $dirtyCcList = implode(',', [
        'dt.system.notifications@insurancemarket.ae',
        ' smartphone.enquiries@insurancemarket.ae',
        '',
        'sandeep.sharma@myalfred.com',
        "rucha.keluskar@myalfred.com\n",
        'digital.transformation.support@myalfred.com',
    ]);

    setApplicationStorageValue(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC, $dirtyCcList);
    setApplicationStorageValue(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL, 'production.approval@insurancemarket.ae');

    $service = new AutomationFailedService();

    $quote = (object) [
        'advisor' => (object) [
            'email' => 'advisor@myalfred.com',
            'name' => 'Device Advisor',
        ],
    ];

    $cc = $service->buildCcEmails($quote, true, PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY);

    $expected = [
        'dt.system.notifications@insurancemarket.ae',
        'smartphone.enquiries@insurancemarket.ae',
        'sandeep.sharma@myalfred.com',
        'rucha.keluskar@myalfred.com',
        'digital.transformation.support@myalfred.com',
    ];

    expect($cc['approvalemail'])->toBe(implode(',', $expected));
    expect($cc['prodemail'])->toBe('production.approval@insurancemarket.ae');
    expect($cc['ccEmails'])->toEqual($expected);
    expect($cc['advisoremail'])->toBe('advisor@myalfred.com');
});

it('builds device email payload with trigger point and reply to', function () {
    config([
        'constants.APP_ENV' => EnvEnum::PRODUCTION,
        'app.url' => 'https://app.test',
    ]);

    $addresses = [
        'dt.system.notifications@insurancemarket.ae',
        'smartphone.enquiries@insurancemarket.ae',
        'sandeep.sharma@myalfred.com',
        'rucha.keluskar@myalfred.com',
        'digital.transformation.support@myalfred.com',
    ];

    setApplicationStorageValue(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC, implode(',', $addresses));
    setApplicationStorageValue(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL, 'production.approval@insurancemarket.ae');
    setApplicationStorageValue(
        ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK,
        'https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T'
    );
    setApplicationStorageValue(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_REPLY_TO, 'production.approval.team@insurancemarket.ae');

    $service = new AutomationFailedService();

    $quote = (object) [
        'uuid' => 'device-uuid',
        'code' => 'REF-123',
        'advisor' => (object) [
            'email' => 'advisor@myalfred.com',
            'name' => 'Device Advisor',
        ],
    ];

    $cc = $service->buildCcEmails($quote, true, PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY);

    $request = new AutomationFailedEmailDataRequest(
        cc: $cc,
        ccEmails: is_array($cc) ? ($cc['ccEmails'] ?? $cc) : [],
        isDeviceNgi: true,
        actionRequired: 'Action text',
        recipientEmail: 'recipient@example.com',
        recipientName: 'Recipient Name',
        statusAPIFailed: 'Status fail',
        processInvolved: PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
        workflowType: 'DEVICE_AUTOMATION_FAILED'
    );

    $emailData = $service->buildEmailData($quote, QuoteTypes::DEVICE->value, $request);

    expect($emailData->imcrmLink)->toBe('https://app.test/personal-quotes/Device/device-uuid');
    expect($emailData->escalationLink)->toBe('https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T');
    expect($emailData->triggerPoint)->toBe('Retrieval of Required Booking Details via API');
    expect($emailData->replyTo)->toBe('production.approval.team@insurancemarket.ae');
    expect($emailData->cc['ccEmails'])->toEqual($addresses);
});
