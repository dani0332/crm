<p><table width="100%" cellpadding="0" cellspacing="0">
    <tr><td width="15%" height="30" valign="top">Submission Date & Time: </td><td width="85%" height="30" valign="top">{{ date('d-m-Y H:i:s') }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Source URL: </td><td width="85%" height="30" valign="top">{{ Request::url() }}</td><tr>
    <tr><td width="15%" height="30" valign="top">AML URL: </td><td width="85%" height="30" valign="top">{{ $amlUrl }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Request Data: </td><td width="85%" height="30" valign="top">{!! $emailAmlData !!}</td><tr>
    <tr><td width="15%" height="30" valign="top">Error HTTP Code: </td><td width="85%" height="30" valign="top">{{ $chAmlStatus }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Error Message: </td><td width="85%" height="30" valign="top">{!! $requestMessage !!}</td><tr>
    </table>
    </p>