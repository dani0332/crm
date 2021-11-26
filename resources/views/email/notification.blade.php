<!DOCTYPE html>
<html>
<body>
    <table class="countries_list">
        <tbody>
            <tr>
                <td>Message:</td>
                <td class="fs15 fw700 text-right">{{$notes ?? ""}}</td>
            </tr>
            <tr>
                <td>First Name:</td>
                <td class="fs15 fw700 text-right">{{ucwords($first_name) ?? ""}}</td>
            </tr>
            <tr>
                <td>Last Name:</td>
                <td class="fs15 fw700 text-right">{{ucwords($last_name) ?? ""}}</td>
            </tr>
            <tr>
                <td>CDBD ID:</td>
                <td class="fs15 fw700 text-right">{{$code ?? ""}}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
