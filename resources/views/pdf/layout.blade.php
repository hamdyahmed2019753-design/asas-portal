<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #1f2430; font-size: 12px; line-height: 1.7; }
        .pdf-head { border-bottom: 2px solid #15A878; padding-bottom: 8px; margin-bottom: 16px; }
        .pdf-brand { font-size: 18px; font-weight: bold; color: #15A878; }
        .pdf-company { color: #6b7280; font-size: 11px; }
        .pdf-meta { color: #6b7280; font-size: 10px; }
        .pdf-title { font-size: 15px; font-weight: bold; margin: 4px 0 14px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { border: 1px solid #e5e7eb; padding: 7px 9px; text-align: right; }
        th { background: #f3f4f6; font-weight: bold; }
        .kv td { border: 0; padding: 5px 0; }
        .kv .k { color: #6b7280; width: 45%; }
        .kv .v { font-weight: bold; }
        .muted { color: #6b7280; }
        .big { font-size: 20px; font-weight: bold; color: #15A878; }
        .center { text-align: center; }
        .seal { border: 2px dashed #15A878; border-radius: 10px; padding: 14px; text-align: center; margin-top: 18px; }
        .pdf-foot { margin-top: 26px; padding-top: 8px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    <div class="pdf-head">
        <table style="border:0;"><tr>
            <td style="border:0;">
                <span class="pdf-brand">{{ $brand['siteName'] }}</span>
                @if (! empty($brand['companyName']))<div class="pdf-company">{{ $brand['companyName'] }}</div>@endif
            </td>
            <td style="border:0; text-align:left;" class="pdf-meta">
                صدر بتاريخ: {{ $brand['issuedAt'] }}<br>
                مرجع: {{ $reference ?? '—' }}
            </td>
        </tr></table>
    </div>

    <div class="pdf-title">@yield('title')</div>

    @yield('content')

    <div class="pdf-foot">
        @if (! empty($brand['supportEmail'])) للدعم: {{ $brand['supportEmail'] }} @endif
        @if (! empty($brand['supportPhone'])) · {{ $brand['supportPhone'] }} @endif
        @if (! empty($brand['taxNumber'])) · الرقم الضريبي: {{ $brand['taxNumber'] }} @endif
        <br>هذا المستند صادر إلكترونيًا من منصة {{ $brand['siteName'] }}.
    </div>
</body>
</html>
