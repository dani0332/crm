<p>
    <p>Your quote status is now updated.</p>

    <table width="100%" cellpadding="0" cellspacing="0" >
    <tr><td width="15%" height="30" valign="top">Updated Date & Time: </td><td width="85%" height="30" valign="top">{{ date('d-m-Y H:i:s') }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Quote Status: </td><td width="85%" height="30" valign="top">{{ $amlQuoteStatus }}</td><tr>
    <tr><td width="15%" height="30" valign="top">AML Link: </td><td width="85%" height="30" valign="top">{{ $amlUrl }}</td><tr>
    <tr><td width="15%" height="30" valign="top">Request Data: </td><td width="85%" height="30" valign="top">
            <table width="100%" cellpadding="3" cellspacing="3">
                <tr><td width="10%">Name:</td><td>{!! $clientFullName !!}</td></tr>
                <tr><td width="10%">Type Name:</td><td>{!! $quoteTypeName !!}</td></tr>
                <tr><td width="10%">Ref-ID:</td><td>{!! $quoteCdbId !!}</td></tr>
            </table>
        </td>
    <tr>
    </table>
    </p>
