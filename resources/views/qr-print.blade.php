<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('system.piggy_bank_qr') }} - {{ $record->unique_box_id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #f8fafc;
        }
        .qr-card {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            max-width: 360px;
            width: 100%;
            box-sizing: border-box;
        }
        .logo {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 26px;
            color: #d97706; /* Amber-600 to match brand */
            margin-bottom: 25px;
            letter-spacing: -0.5px;
        }
        .qr-container {
            background-color: white;
            padding: 15px;
            border-radius: 16px;
            border: 1px solid #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }
        .unique-id {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #1e293b;
            margin: 0 0 8px 0;
        }
        .shop-name {
            font-size: 15px;
            font-weight: 500;
            color: #64748b;
            margin: 0;
        }
        .info-tag {
            display: inline-block;
            margin-top: 15px;
            background-color: #fef3c7;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 10px;
            border-radius: 9999px;
        }
        @media print {
            body {
                background-color: white;
                height: auto;
            }
            .qr-card {
                border: none;
                box-shadow: none;
                padding: 0;
                margin-top: 40px;
            }
            .info-tag {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="qr-card">
        <div class="logo">{{ __('system.kumbara_takip_sistemi') }}</div>
        <div class="qr-container">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(260)->format('svg')->generate($record->unique_box_id) !!}
        </div>
        <div class="unique-id">{{ $record->unique_box_id }}</div>
        <div class="shop-name">{{ $record->shop?->name ?? __('system.piggy_bank') }}</div>
        <div class="info-tag">{{ __('system.piggy_bank_qr') }}</div>
    </div>
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
