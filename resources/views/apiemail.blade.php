<p><table width="100%" cellpadding="0" cellspacing="0">
    <tr><td width="15%" height="30" valign="top">Submission Date & Time: </td> <td width="85%" height="30" valign="top">{{ date('d-m-Y H:i:s') }}</td><tr>
    <tr><td width="15%" height="30" valign="top">URL: </td> <td width="85%" height="30" valign="top">{{ Request::url() }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Type of Insurance: </td> <td width="85%" height="30" valign="top">{{ $insuranceName }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Curl Error#: </td> <td width="85%" height="30" valign="top">{{ $curlErrnoCentr }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Curl Error: </td> <td width="85%" height="30" valign="top">{{ $curlErrorCentr }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Curl Message: </td> <td width="85%" height="30" valign="top">{!! $curlMesg !!}</td><tr>
    <tr><td width="15%" height="30" valign="top">Form Data: </td> <td width="85%" height="30" valign="top">{!! $emailCntrData !!}</td><tr>
    </table>
    </p>