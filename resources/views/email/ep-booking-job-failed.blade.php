<strong>Dear Team,</strong>
<br><br>

@if($isSageBooking ?? false)
We would like to inform you that the Sage booking of Embedded Product - {{ $epProductName ?? 'Unknown' }} has failed for RefID: {{ $refId ?? 'Unknown' }}.
@else
We would like to inform you that Embedded Product - {{ $epProductName ?? 'Unknown' }} has failed for RefID: {{ $refId ?? 'Unknown' }}.
@endif
<br><br>

<strong>Action Required:</strong> Please review the lead and take the necessary steps to resolve the issue.<br>
<br><br>

IMCRM link: <a href="{{ $imcrmLink ?? '#' }}">{{ $refId ?? '-' }}</a>.
<br><br>

If further assistance is needed, please escalate this issue to the engineering department.
<br><br>

Thank you for your prompt attention to this matter.
<br><br>

Best regards,
<br>

Support Team