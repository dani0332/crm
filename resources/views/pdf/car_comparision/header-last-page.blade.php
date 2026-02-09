<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        #header-content {
            display: block;
            padding: 10px 0;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
            width: 100%;
            padding: 0px 30px 0px 30px;
        }

        .logo-container img {
            display: inline-block;
            height: auto;
            max-height: 70px;
            width: auto;
        }
        
        /* Logo-specific styles */
        .main-logo {
            max-height: 70px;
            margin-right: 30px;
        }
        
        .alfred-logo {
            max-height: 90px;
            margin-left: 30px;
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
        <div class="logo-container">
            <img src="{{ public_path('images/logo-25k.png') }}" alt="Logo" class="main-logo">
            <img src="https://cdn-prod.myalfred.me/media/assets/insurancemarket-halfalfredstandingfoldinghandsinsuit.png" alt="Alfred Logo" class="alfred-logo">
        </div>
    </div> 
</body>
</html>
