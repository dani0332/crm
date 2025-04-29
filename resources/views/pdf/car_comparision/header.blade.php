<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        #header-content {
            display: block;
        }

        /* Hide the header on the last page using wkhtmltopdf-specific CSS */
        .hide-on-last-page {
            display: block;
        }

        @page {
            display:""; 
        }

        /* This works only if wkhtmltopdf CSS engine supports "last page" logic, which is limited */
    </style>
</head>
<body>
    <div id="header-content">
        <div class="logo-container" style="text-align: center;">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-new.png'))) }}" 
                 alt="Logo" style="max-width: 50%; height: auto;">
                 <div style="display: none;">[page] == [toPage]</div>

            </div>
        <div style="text-align: center;">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/headset.png'))) }}" 
                 alt="Logo" style="max-width: 50%; height: auto;">
            <div style="font-size: 0;">Page [page] of [toPage]</div>
        </div>
    </div>

 
</body>
</html>
