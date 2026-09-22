<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Voucher WiFi</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 0;
        }

        .voucher-card {
            border: 1px dashed #888;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }

        .brand {
            font-size: 6.5pt;
            letter-spacing: 0.5px;
            color: #555;
            margin: 0 0 4px 0;
        }

        .title {
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0 0 6px 0;
        }

        table.fields {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        table.fields td {
            padding: 1.5px 0;
            text-align: left;
        }

        table.fields td.label {
            color: #555;
            width: 38%;
        }

        table.fields td.value {
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .footer {
            font-size: 6pt;
            color: #777;
            margin: 6px 0 0 0;
        }

        /* Grid layout — A4 / A5 */
        table.grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }

        table.grid td {
            vertical-align: top;
            page-break-inside: avoid;
        }

        /* Single-column layout — Thermal / Card */
        .single-wrapper {
            padding: 6px;
        }

        /* Card (CR80, 85.6x54mm) has very little vertical room — every
           field must fit inside one physical card, so this trims font
           sizes and spacing well below the other layouts' defaults. */
        .paper-card .single-wrapper {
            padding: 2px;
        }

        .paper-card .voucher-card {
            padding: 4px 6px;
        }

        .paper-card .brand {
            font-size: 5pt;
            margin-bottom: 2px;
        }

        .paper-card .title {
            font-size: 8pt;
            margin-bottom: 3px;
        }

        .paper-card table.fields {
            font-size: 6.5pt;
        }

        .paper-card table.fields td {
            padding: 0.5px 0;
        }

        .paper-card .footer {
            font-size: 4.5pt;
            margin-top: 2px;
        }
    </style>
</head>
<body class="paper-{{ $paperSize->value }}">
    @if (in_array($paperSize->value, ['thermal', 'card']))
        @foreach ($vouchers as $voucher)
            <div class="single-wrapper" @if (! $loop->last) style="page-break-after: always;" @endif>
                @include('pdf.partials.card', ['voucher' => $voucher])
            </div>
        @endforeach
    @else
        <table class="grid">
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $voucher)
                        <td style="width: {{ 100 / $columns }}%;">
                            @include('pdf.partials.card', ['voucher' => $voucher])
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
