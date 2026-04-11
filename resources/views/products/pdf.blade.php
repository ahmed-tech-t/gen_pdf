<!DOCTYPE html>
<html dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 1cm;
        }


        @font-face {
            font-family: 'ArialCustom';
            src: url("{{ storage_path('fonts/arial.ttf') }}") format("truetype");
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'ArialCustom', sans-serif;
            direction: ltr;
            /* Ar-PHP handles the RTL reversal */
            text-align: right;
            margin: 0;
            padding: 0;
            margin-bottom: 50px;
        }

        .product-page {
            page-break-after: always;
            width: 100%;
        }

        .title {
            text-align: center;
            font-size: 24pt;
            font-weight: normal;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .image-container {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }

        /* This forces the image to fill the width */
        .full-image {
            width: 100%;
            height: auto;
            max-height: 500px;
            /* Adjust this so it doesn't push data to next page */
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f9f9f9;
        }

        .details-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: right;
        }

        .label {
            background-color: #eee;
            width: 30%;
        }

        @page {
            margin: 1cm;
        }

        footer {
            position: fixed;
            bottom: -10px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
        }

        /* Create a counter for the pages */
        .page-number:after {
            content: counter(page) " {{ $Arabic->utf8Glyphs("الصفحة") }} ";
        }
    </style>


</head>

<body>

    <footer>
        <span class="page-number"></span>
    </footer>

    @foreach($products as $index => $item)

        <div class="product-page">
            <h1 class="title">{{ $item['data']['description'] ?? 'Product' }}</h1>

            <div class="image-container">
                @if($item['image'])
                    <img src="data:image/jpeg;base64,{{ $item['image'] }}" class="full-image">
                @endif
            </div>

            <table class="details-table">
                <tr>

                    <td>{{ $item['data']['size'] ?? '-' }}</td>
                    <td class="label">{{ $Arabic->utf8Glyphs("المقاس") }}</td>
                </tr>
                <tr>
                    <td>{{ $item['data']['unit'] ?? '-' }}</td>
                    <td class="label">{{ $Arabic->utf8Glyphs("الوحدة") }}</td>
                </tr>
                <tr>
                    <td> {{$Arabic->utf8Glyphs("جنيه") }} {{ number_format($item['data']['price'] ?? 0, 2) }} </td>
                    <td class="label">{{ $Arabic->utf8Glyphs("السعر") }}</td>
                </tr>
            </table>


        </div>
    @endforeach
</body>


</html>