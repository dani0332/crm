<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Questionnaire</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #2c3e50;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
        }
        
        .main-title {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 25px;
            margin-top: 0;
        }
        
        .questions-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .question-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .question-text {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 20px;
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
            top: 5px;
        }
        
        .declaration-list {
            display: flex;
            flex-direction: column;
        }
        
        .declaration-item {
            padding: 15px 0;
            margin-bottom: 10px;
            clear: both;
            page-break-inside: avoid;
            break-inside: avoid;
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
            content: "✓";
        }
        
        .checkbox:not(.checked) {
            background-color: white;
            border: 2px solid #6c757d;
            color: transparent;
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
        
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="main-title">Health Questionnaire</h1>
        </div>

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
            <div class="section">
                <h2 class="section-title">Let's See If You Are Eligible</h2>
                <div class="questions-grid">
                    @foreach($questions as $index => $question)
                        @if(isset($question['type']) && $question['type'] === 'select')
                        <div class="question-card">
                            <p class="question-text">{{ str_replace("\n", " ", $question['title'] ?? 'N/A') }}</p>
                            <div class="radio-group">
                                @php
                                    // Alternate between YES and NO for testing
                                    $isYesSelected = ($index % 2 == 0);
                                @endphp
                                <span class="radio-option {{ $isYesSelected ? 'selected' : '' }}">YES</span>
                                <span class="radio-option {{ !$isYesSelected ? 'selected' : '' }}">NO</span>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($declarations))
            <div class="section">
                <h2 class="section-title">We Need Your Declarations</h2>
                <div class="declaration-list">
                    @foreach($declarations as $index => $declaration)
                        @if(isset($declaration['type']) && $declaration['type'] === 'checkbox')
                        <div class="declaration-item">
                            @php
                                // Alternate between checked and unchecked for testing
                                $isChecked = ($index % 2 == 0);
                            @endphp
                            <span class="checkbox {{ $isChecked ? 'checked' : '' }}"></span>
                            <p class="declaration-text">{{ str_replace("\n", " ", $declaration['title'] ?? 'N/A') }}</p>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @else
            <div class="section">
                <h2 class="section-title">We Need Your Declarations</h2>
                <p>No declarations found in the data.</p>
            </div>
            @endif
        @endif

    </div>
</body>
</html>
