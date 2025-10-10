<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap");
    </style>
    <!--[if mso]>
      <noscript>
        <xml>
          <o:OfficeDocumentSettings
            xmlns:o="urn:schemas-microsoft-com:office:office"
          >
            <o:PixelsPerInch>96</o:PixelsPerInch>
          </o:OfficeDocumentSettings>
        </xml>
      </noscript>
      <style>
        td,
        th,
        div,
        p,
        a,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
          font-family: "Segoe UI", sans-serif;
          mso-line-height-rule: exactly;
        }
      </style>
    <![endif]-->
    <style>
        .hover-underline:hover {
            text-decoration-line: underline !important;
        }

        @media (prefers-color-scheme: dark) {
            .dark-border-_0e0d0d {
                border-color: #0e0d0d !important;
            }

            .dark-important-bg-slate-800 {
                background-color: #1e293b !important;
            }

            .dark-bg-gray-800 {
                background-color: #1f2937 !important;
            }

            .dark-bg-slate-800 {
                background-color: #1e293b !important;
            }

            .dark-bg-slate-900 {
                background-color: #0f172a !important;
            }

            .dark-bg-none {
                background-image: none !important;
            }

            .dark-important-text-slate-200 {
                color: #e2e8f0 !important;
            }

            .dark-important-text-white {
                color: #fff !important;
            }

            .dark-text-slate-200 {
                color: #e2e8f0 !important;
            }

            .dark-text-slate-600 {
                color: #475569 !important;
            }
        }

        @media (max-width: 600px) {
            .sm-important-my-1 {
                margin-top: 4px !important;
                margin-bottom: 4px !important;
            }

            .sm-mb-0_5 {
                margin-bottom: 2px !important;
            }

            .sm-block {
                display: block !important;
            }

            .sm-table-cell {
                display: table-cell !important;
            }

            .sm-hidden {
                display: none !important;
            }

            .sm-h-4 {
                height: 16px !important;
            }

            .sm-h-9 {
                height: 36px !important;
            }

            .sm-w-60px {
                width: 60px !important;
            }

            .sm-w-auto {
                width: auto !important;
            }

            .sm-w-full {
                width: 100% !important;
            }

            .sm-max-w-28 {
                max-width: 112px !important;
            }

            .sm-max-w-full {
                max-width: 100% !important;
            }

            .sm-object-cover {
                object-fit: cover !important;
            }

            .sm-object-right {
                object-position: right !important;
            }

            .sm-p-5_5px_17_5px {
                padding: 5.5px 17.5px !important;
            }

            .sm-px-4 {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .sm-py-1 {
                padding-top: 4px !important;
                padding-bottom: 4px !important;
            }

            .sm-pt-0 {
                padding-top: 0 !important;
            }

            .sm-pt-2 {
                padding-top: 8px !important;
            }

            .sm-text-center {
                text-align: center !important;
            }

            .sm-important-text-10px {
                font-size: 10px !important;
            }

            .sm-text-10px {
                font-size: 10px !important;
            }

            .sm-text-12px {
                font-size: 12px !important;
            }

            .sm-text-7_5px {
                font-size: 7.5px !important;
            }

            .sm-text-8px {
                font-size: 8px !important;
            }

            .sm-text-base {
                font-size: 16px !important;
            }

            .sm-text-xs {
                font-size: 12px !important;
            }

            .sm-important-leading-3_5 {
                line-height: 14px !important;
            }

            .sm-leading-8 {
                line-height: 32px !important;
            }
        }

        @media (max-width: 425px) {
            .xs-w-45px {
                width: 45px !important;
            }

            .xs-text-10px {
                font-size: 10px !important;
            }
        }

        /* Health Questionnaire Specific Styles */
        .health-question {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .health-question-text {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 15px;
            color: #2c3e50;
            margin-top: 0;
        }

        .radio-group {
            margin-top: 10px;
        }

        .radio-option {
            display: inline-block;
            font-size: 15px;
            color: #6c757d;
            margin-right: 30px;
            position: relative;
            padding-left: 25px;
        }

        .radio-option.selected {
            color: #007bff;
            font-weight: 600;
        }

        .radio-option::before {
            content: "";
            width: 16px;
            height: 16px;
            border: 2px solid #6c757d;
            border-radius: 50%;
            background-color: white;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
            position: absolute;
            left: 0;
            top: 2px;
        }

        .radio-option.selected::before {
            border-color: #007bff;
            background-color: #007bff;
        }

        .radio-option.selected::after {
            content: "";
            width: 6px;
            height: 6px;
            background-color: white;
            border-radius: 50%;
            position: absolute;
            left: 5px;
            top: 7px;
        }

        .declaration-item {
            padding: 15px 0;
            margin-bottom: 10px;
            clear: both;
            page-break-inside: avoid;
            break-inside: avoid;
            border-bottom: 1px solid #e9ecef;
        }

        .declaration-item:last-child {
            border-bottom: none;
        }

        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #6c757d;
            border-radius: 4px;
            background-color: white;
            float: left;
            margin-right: 15px;
            margin-top: 3px;
            text-align: center;
            line-height: 15px;
            font-size: 14px;
            color: transparent;
            font-weight: bold;
        }

        .checkbox.checked {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .checkbox.checked::after {
            content: "\2713";
            color: white;
            font-size: 12px;
            font-weight: bold;
            line-height: 16px;
            text-align: center;
            display: block;
            margin-top: -1px;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .declaration-text {
            font-size: 15px;
            line-height: 1.6;
            color: #2c3e50;
            margin: 0;
            margin-left: 35px;
            margin-top: -2px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .section-title {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 25px;
            margin-top: 0;
        }
    </style>
</head>

<body class="light-bg-slate-900"
    style="margin: 0; width: 100%; background-color: #fff; padding: 0; -webkit-font-smoothing: antialiased; word-break: break-word">
    <div role="article" aria-roledescription="email" aria-label lang="en">
        <div class="light-bg-slate-800 light-important-text-slate-200"
            style="background-color: #F1F5F9; padding: 26px 12px; font-family: Verdana , Geneva , sans-serif; color: #333333">
            <table class="light-border-_0e0d0d light-bg-slate-900"
                style="margin-left: auto; margin-right: auto; width: 100%; max-width: 625px; border-bottom-right-radius: 15px; border-bottom-left-radius: 15px; border: 1px solid #c7d0d4; background-color: #fff; padding-top: 8px"
                cellpadding="0" cellspacing="0" role="presentation">
                <tbody>
                    <tr>
                        <td>
                            <!-- Header Section -->
                            <div class="sm-px-4 sm-py-1" style="padding: 14px 28px">
                                <table style="width: 100%;" cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td style="padding-bottom: 8px">
                                            <table style="width: 100%;" cellpadding="0" cellspacing="0"
                                                role="presentation">
                                                <tbody>
                                                    <td class="sm-text-center sm-w-full sm-block"
                                                        style="text-align: left">
                                                        <a href><img
                                                                src="https://cdn.alfred.ae/assets/logo/im/IM-23k.png"
                                                                alt="Insurance Market"
                                                                style="vertical-align: middle; line-height: 1; border: 0; width: 100%; max-width: 250px">
                                                        </a>
                                                    </td>
                                                    <td class="sm-w-full sm-block sm-text-center"
                                                        style="text-align: right; color: #1d83bc">
                                                        <div class="sm-pt-2" style="font-size: 14px">
                                                            <b>Health Questionnaire</b>
                                                        </div>
                                                    </td>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <hr style="margin: 0;">
                            
                            <!-- Main Content Section -->
                            <div class="sm-px-4" style="padding: 14px 28px">

                                <!-- Health Questionnaire Content -->
                                <div style="margin-top: 20px">
                                    {{-- 
                                    <table style="width: 100%; vertical-align: middle" cellpadding="0" cellspacing="0"
                                        role="presentation">
                                        <tbody>
                                            <td>
                                                <p class="light-text-slate-200 sm-text-12px sm-text-base"
                                                    style="margin-top: 4px; margin-bottom: 4px; display: inline-block; padding: 4px; font-family: inherit; font-weight: 700; color: #333333; font-size: 22px">
                                                    Health Questionnaire
                                                </p>
                                            </td>
                                            <td style="width: 114px; text-align: end">
                                                <img class="sm-max-w-28"
                                                    src="https://cdn.alfred.ae/assets/alfred/alfredPoses/AlfredNew.png"
                                                    alt="alfred"
                                                    style="vertical-align: middle; line-height: 1; border: 0; margin-left: auto; max-height: 114px; max-width: 114px; object-fit: fill; padding-right: 8px">
                                            </td>
                                        </tbody>
                                    </table>
                                    --}}

                                    <!-- Questions Section -->
                                    @if(isset($data['health_questionnaire']['fields']))
                                        @php
                                            $questions = [];
                                            $declarations = [];

                                            foreach($data['health_questionnaire']['fields'] as $field) {
                                                if(isset($field['form_type']) && $field['form_type'] === 'form' && isset($field['fields'])) {
                                                    if(strpos($field['form_title'], 'eligible') !== false || strpos($field['form_title'], 'answer') !== false) {
                                                        $questions = $field['fields'];
                                                    } elseif(strpos($field['form_title'], 'declaration') !== false || strpos($field['form_name'], 'Declaration') !== false) {
                                                        $declarations = $field['fields'];
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if(!empty($questions))
                                        <div style="margin-top: 10px">
                                            <h2 class="section-title">Let's See If You Are Eligible</h2>
                                            @foreach($questions as $index => $question)
                                                @if(isset($question['type']) && $question['type'] === 'select')
                                                <div class="health-question">
                                                    <p class="health-question-text">{{ str_replace("\n", " ", $question['title'] ?? 'N/A') }}</p>
                                                    <div class="radio-group">
                                                        @php
                                                            // Get the actual value from the API response
                                                            $selectedValue = $question['value'] ?? $question['selected_value'] ?? null;
                                                            $isYesSelected = ($selectedValue === 'yes' || $selectedValue === 'Yes' || $selectedValue === 'YES' || $selectedValue === '1' || $selectedValue === 1);
                                                            $isNoSelected = ($selectedValue === 'no' || $selectedValue === 'No' || $selectedValue === 'NO' || $selectedValue === '0' || $selectedValue === 0);
                                                        @endphp
                                                        <span class="radio-option {{ $isYesSelected ? 'selected' : '' }}">YES</span>
                                                        <span class="radio-option {{ $isNoSelected ? 'selected' : '' }}">NO</span>
                                                    </div>
                                                </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        @endif

                                        @if(!empty($declarations))
                                        <div style="margin-top: 20px">
                                            <h2 class="section-title">We Need Your Declarations</h2>
                                            <div class="light-bg-gray-800"
                                                style="border-radius: 8px; background-color: #F7F7F7; padding: 20px">
                                                @foreach($declarations as $index => $declaration)
                                                    @if(isset($declaration['type']) && $declaration['type'] === 'checkbox')
                                                    <div class="declaration-item">
                                                        @php
                                                            // Get the actual checked state from the API response
                                                            $isChecked = false;
                                                            if (isset($declaration['value'])) {
                                                                $isChecked = ($declaration['value'] === true || $declaration['value'] === 'true' || $declaration['value'] === '1' || $declaration['value'] === 1);
                                                            } elseif (isset($declaration['checked'])) {
                                                                $isChecked = ($declaration['checked'] === true || $declaration['checked'] === 'true' || $declaration['checked'] === '1' || $declaration['checked'] === 1);
                                                            } elseif (isset($declaration['selected'])) {
                                                                $isChecked = ($declaration['selected'] === true || $declaration['selected'] === 'true' || $declaration['selected'] === '1' || $declaration['selected'] === 1);
                                                            }
                                                        @endphp
                                                        <span class="checkbox {{ $isChecked ? 'checked' : '' }}"></span>
                                                        <p class="declaration-text">{{ str_replace("\n", " ", $declaration['title'] ?? 'N/A') }}</p>
                                                    </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        @else
                                        <div style="margin-top: 20px">
                                            <h2 class="section-title">We Need Your Declarations</h2>
                                            <div class="light-bg-gray-800"
                                                style="border-radius: 8px; background-color: #F7F7F7; padding: 20px; text-align: center;">
                                                <p class="light-text-slate-200 sm-text-12px"
                                                    style="margin: 20px 0; font-family: inherit; font-size: 14px; color: #5D697B; font-style: italic;">
                                                    No declarations found in the data.
                                                </p>
                                            </div>
                                        </div>
                                        @endif
                                    @else
                                    <div style="margin-top: 10px">
                                        <div class="light-bg-gray-800"
                                            style="border-radius: 8px; background-color: #F7F7F7; padding: 20px; text-align: center;">
                                            <p class="light-text-slate-200 sm-text-12px"
                                                style="margin: 20px 0; font-family: inherit; font-size: 14px; color: #5D697B; font-style: italic;">
                                                No health questionnaire data available.
                                            </p>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>