<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $item->employee_name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .info-label {
            width: 120px;
            font-weight: bold;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .main-table th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }
        .main-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .section-title {
            background-color: #eee;
            font-weight: bold;
            padding: 5px 8px;
            border: 1px solid #ddd;
        }
        .amount {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .netto-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0f7ff;
            border: 2px solid #0056b3;
            text-align: right;
        }
        .netto-label {
            font-size: 14px;
            font-weight: bold;
            color: #0056b3;
        }
        .netto-amount {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }
        .footer {
            margin-top: 40px;
        }
        .signature-table {
            width: 100%;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Slip Gaji Karyawan</h1>
        <p>Periode: {{ $item->period->code }} ({{ date('d-m-Y', strtotime($item->period->start_date)) }} s/d {{ date('d-m-Y', strtotime($item->period->end_date)) }})</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">NIK</td>
            <td>: {{ $item->employee->nik_internal }}</td>
            <td class="info-label">Departemen</td>
            <td>: {{ $item->department_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Nama Karyawan</td>
            <td>: {{ $item->employee_name }}</td>
            <td class="info-label">Jabatan</td>
            <td>: {{ $item->employee->position->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Bank / Rekening</td>
            <td>: {{ $item->employee->bank_name ?? '-' }} / {{ $item->employee->bank_account ?? '-' }}</td>
            <td class="info-label">Cabang</td>
            <td>: {{ $item->branch_name ?? '-' }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>Deskripsi Komponen</th>
                <th class="amount" width="150">Jumlah (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2" class="section-title">PENGHASILAN (EARNINGS)</td>
            </tr>
            @foreach($earnings as $earning)
            <tr>
                <td>{{ $earning->component_name ?: $earning->component_code }}</td>
                <td class="amount">{{ number_format($earning->amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL PENGHASILAN KOTOR (BRUTO)</td>
                <td class="amount">{{ number_format($item->total_bruto, 0, ',', '.') }}</td>
            </tr>

            <tr>
                <td colspan="2" class="section-title">POTONGAN (DEDUCTIONS)</td>
            </tr>
            @foreach($deductions as $deduction)
            <tr>
                <td>{{ $deduction->component_name ?: $deduction->component_code }}</td>
                <td class="amount">({{ number_format($deduction->amount, 0, ',', '.') }})</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL POTONGAN</td>
                <td class="amount">({{ number_format($item->total_deduction, 0, ',', '.') }})</td>
            </tr>
        </tbody>
    </table>

    <div class="netto-box">
        <span class="netto-label">TAKE HOME PAY (NETTO)</span><br>
        <span class="netto-amount">IDR {{ number_format($item->total_netto, 0, ',', '.') }}</span>
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ $generated_at }}</p>
        <table class="signature-table">
            <tr>
                <td class="signature-box">
                    <p>Penerima,</p>
                    <div class="signature-space"></div>
                    <p>( {{ $item->employee_name }} )</p>
                </td>
                <td></td>
                <td class="signature-box">
                    <p>HRD / Finance,</p>
                    <div class="signature-space"></div>
                    <p>( ................................ )</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
