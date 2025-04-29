<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Raleway:wght@400;700&family=Prompt:wght@300;400;500&family=Prompt:wght@400;600&family=Inter:wght@500&family=Poppins:wght@300;400;500&display=swap');
        
    body, html {
        margin: 0;
        padding: 0;
        width: 100%;
    }

    body{
        font-family: 'Prompt', sans-serif;
    }
    .footer {
        -webkit-print-color-adjust: exact;
        background: #1F84BD !important;
        color: white;
        width: calc(100% + 25px); 
               height: 160px;
        box-sizing: border-box;
        /* position:relative; */
        bottom: 0;
        left: 0;
        padding: 0px 10px;
        margin-left: -10px;
        margin-right: -10px;
    }

    .trademark {
        font-weight: 600;
        font-size: 16px;
        text-align: center;
        width: 100%;
        /* padding-top:1px; */
        font-family: 'Raleway', sans-serif;
    }

    .footer-content {
        width: 100%;
        padding: 0;
        margin: 0;
        height:80px;
    }

    .footer-box{
        border-radius: 24px;
        border: 2px solid #CF9E3C;
        padding: 6px 10px;
        text-align: left;
    }

    .certifications {
        width: 36%;
        border: 2px solid #CF9E3C;
        border-radius: 20px;
        background: #1F84BD;
        float: left;
        padding: 6px 10px;
        font-family: 'Poppins', sans-serif;
        margin: auto 7px;

    }

    .cert-details {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        width: 100%;
        font-size: 10px;
        line-height: 1.5;
        text-align: left;
    }

    .cert-item {
        font-weight: 400;
        font-size: 8px;
        line-height: 1.1;
        margin: 1px 0;
        text-align: left;
        padding-left: 0;
    }

    .address {
        width: 24%;
        background-color: #1D83BC;
        border: 2px solid #CF9E3C;
        border-radius: 20px;
        padding: 10px;
        display: flex;
        font-weight: 400;
        font-size:12px;
        line-height: 1.5;
        text-align: left;
        float: left;
        margin: auto 7px;

        font-family: 'Poppins', sans-serif;
    }

    .address-text {
        width: 80%;
        display: flex;
        float:left;

    }

    .address-icon {
        width: 20%;
        float:left;
    }

    .address-icon img {
        width: 50%;
        height: auto;
        margin-top: 28px;
        margin-left: 20px;
    }

    .address p {
        text-align: left;
        margin: 0;
        padding: 0;
    }

    .advisor {
        width: 28%;
        background-color: #1D83BC;
        border: 2px solid #CE9D3B;
        border-radius: 20px;
        padding: 5px 10px;
        float: left;
        margin: auto 7px;

    }

    .advisor-title {
        font-weight: 400;
        font-size: 12px;
        line-height: 1;
        margin: 5px auto;
        padding:0px; 
        text-align: left;
        font-family: 'Poppins', sans-serif;
    }

    .advisor-details {
    }

    .advisor-image {
        width: 60px;
        height: 60px;
        border-radius:50%;
        background-color: #7DBCD8;
        overflow: hidden;
        float:left; 
        margin:auto; 
        /* text-align: center; */
    }

    

    .advisor-image img {
        width: 100%;
        height: auto;
        margin-bottom: -2px;
    }

    .advisor-info {
        float:left;
        padding-left: 10px;
        line-height: 1.5;
    }

    .advisor-name,
    .advisor-direct {
        font-weight: 500;
        font-size: 10px;
        /* line-height: 1; */
        margin: 0;
        font-family: 'Raleway', sans-serif;
    }

    .advisor-email {
        font-weight: 500;
        font-size: 8px;
        line-height: 1.5;
        margin: 0;
        color: white;
        font-family: 'Raleway', sans-serif;
        text-decoration: none;
    }

    .mobile-container {
        display: flex;
        align-items: center;
        width: 100%;
        gap: 5px;
    }

    .mobile-label {
        font-weight: 700;
        font-size: 8px;
        line-height: 1.5;
        margin: 0;
        float:left;
        font-family: 'Raleway', sans-serif;
    }

    .mobile-number {
        font-weight: 400;
        font-size: 8px;
        line-height: 1.5;
        display: flex;
        align-items: center;
        gap: 5px;
        margin: 0;
        float:left;
        font-family: 'Prompt', sans-serif;
    }
</style>
</head>
<body>
<footer class="footer">
    <div class="trademark">
        <p style="padding-top: 5px;">InsuranceMarket.ae is the registered trademark of AFIA Insurance Brokerage Services LLC</p>
    </div>
    <div class="footer-content">
        <!-- certifications section -->
        <div class="certifications">
            <div class="cert-details">
                <p class="cert-item">UAE Central Bank Registration No. 85</p>
                <p class="cert-item">Registered Member of Gulf Insurance Federation</p>
                <p class="cert-item">Registered Member of Emirates Insurance Association, number B6</p>
                <p class="cert-item">Department of Economy & Tourism in Dubai Trade Licence No. 238534</p>
                <p class="cert-item">Registered member of the DIFC Insurance Association with membership number 10049</p>
                <p class="cert-item">Holder of Health Insurance Intermediary Permit ID No. BRK-00003 from Dubai Health
                    Authority</p>
                <p class="cert-item">Registered member of Insurance Business Group under the Dubai Chamber of Commerce
                    <br />and Industry, number 34774</p>
            </div>
        </div>

        <!-- address section -->
        <div class="address">
            <div class="address-text">
                <p>27th floor, Control Tower, Detroit road, Motor city, Dubai.<br>United Arab Emirates.<br>PO Box -
                    26423</p>
            </div>
            <div class="address-icon">
                @php
                    $linkIcon = public_path('images/quote_plans_pages/ecom_home/open_in_new_icon.png');
                @endphp
                <a href="https://google.com" target="_blank">
                    <img src="{{ $linkIcon }}" alt="Link Icon" style="width: 50%; height: auto;">
                </a>
            </div>
        </div>

        <!-- advisor section -->
        <div class="advisor">
            <p class="advisor-title">Your insurance advisor is:</p>
            <div class="advisor-details">
                <div class="advisor-image">
                    <div class="advisor-image-small">
                        <img src="{{ public_path('images/headset-1.png') }}" 
                        alt="Headset" />
                    </div>
                </div>
                <div class="advisor-info">

                    @if(isset($quote->advisor->name))
                        <p class="advisor-name"><strong>Name:</strong> {{$quote->advisor->name }}</p>
                    @endif
                    
                    @if(isset($quote->advisor->email))
                        <p class="advisor-name"><a style="text-decoration: none;color:white" href="mailto:{{$quote->advisor->email}}"><strong>Email:</strong> {{$quote->advisor->email}} </a></p>    
                    @endif
                
                    @if(isset($quote->advisor->mobile_no))
                        <p class="advisor-name" href="tel:{{$quote->advisor->mobile_no}}"><strong>Mobile number: </strong>{{ $quote->advisor->mobile_no }} <img src="{{ public_path('images/whatsapp-small.png') }}" style="max-width: 10%; height: auto;margin-left: 1px; vertical-align: baseline;" /> </p>    
                    @endif
                    @if(isset($quote->advisor->landline_no))
                        <p class="advisor-name"><strong>Direct Line: </strong>{{ $quote->advisor->landline_no }}</p>    
                    @endif
                </div>
            </iv>
        </div>
    </div>
</footer>