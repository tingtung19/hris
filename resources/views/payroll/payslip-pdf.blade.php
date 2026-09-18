<!doctype html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px}table{width:100%;border-collapse:collapse;margin-top:20px}td,th{border:1px solid #ccc;padding:6px;text-align:left}.number{text-align:right}</style></head>
<body>
<h2>Internal HRIS - Payslip</h2>
<p><strong>Periode:</strong> {{ $payroll->period_name }}</p>
<p><strong>Karyawan:</strong> {{ $payroll->employee_number }} - {{ $payroll->first_name }} {{ $payroll->last_name }}</p>
<table><thead><tr><th>Komponen</th><th>Tipe</th><th class="number">Jumlah</th></tr></thead><tbody>
@foreach($details as $detail)<tr><td>{{ $detail->component_name }}</td><td>{{ ucfirst($detail->type) }}</td><td class="number">Rp {{ number_format($detail->amount, 0, ',', '.') }}</td></tr>@endforeach
</tbody></table>
<p><strong>Gaji kotor:</strong> Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</p>
<p><strong>Total potongan:</strong> Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</p>
<p><strong>Gaji bersih:</strong> Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</p>
</body>
</html>
