<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Slip Gaji {{ $payroll->employee->name }} - {{ $payroll->period->name }}</title>
<style>
body{font-family:Arial,sans-serif;background:#f8fafc;color:#111827;margin:0;padding:24px}.sheet{max-width:850px;margin:auto;background:#fff;border:1px solid #d1d5db;padding:28px}.head{display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #111827;padding-bottom:16px}.muted{color:#6b7280}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:18px 0}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{padding:9px;border-bottom:1px solid #e5e7eb;text-align:left}.right{text-align:right}.net{font-size:20px;font-weight:700}.actions{max-width:850px;margin:0 auto 16px;display:flex;gap:8px}.btn{padding:9px 13px;background:#1e3a8a;color:#fff;text-decoration:none;border:0;border-radius:6px;cursor:pointer}.secondary{background:#475569}@media print{body{background:#fff;padding:0}.actions{display:none}.sheet{border:0;max-width:none;padding:0}}
</style>
</head>
<body>
<div class="actions"><a class="btn secondary" href="{{ $backUrl }}">Kembali</a><button class="btn" onclick="window.print()">Cetak / Simpan PDF</button></div>
<div class="sheet">
<div class="head"><div><h1 style="margin:0">SLIP GAJI</h1><div class="muted">{{ $payroll->period->name }}</div></div><div class="right"><strong>SDM Pabrik</strong><br><span class="muted">Finalized {{ $payroll->finalized_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</span></div></div>
<div class="grid">
<div><strong>NIP</strong><br>{{ $payroll->employee->nip }}</div>
<div><strong>Pegawai</strong><br>{{ $payroll->employee->name }}</div>
<div><strong>Departemen</strong><br>{{ $payroll->employee->department->name }}</div>
<div><strong>Periode</strong><br>{{ $payroll->period->period_start->format('d-m-Y') }} s/d {{ $payroll->period->period_end->format('d-m-Y') }}</div>
</div>

<h3>Ringkasan Kehadiran</h3>
<table>
<tr><th>Jadwal</th><td class="right">{{ $payroll->scheduled_days }}</td><th>Present</th><td class="right">{{ $payroll->present_days }}</td></tr>
<tr><th>Late</th><td class="right">{{ $payroll->late_days }} hari / {{ $payroll->late_minutes }} menit</td><th>Absent</th><td class="right">{{ $payroll->absent_days }}</td></tr>
<tr><th>Leave</th><td class="right">{{ $payroll->leave_days }}</td><th>Permit</th><td class="right">{{ $payroll->permit_days }}</td></tr>
<tr><th>Approved Overtime</th><td class="right">{{ $payroll->approved_overtime_minutes }} menit</td><th>Incomplete</th><td class="right">{{ $payroll->incomplete_days }}</td></tr>
</table>

<h3>Komponen Penghasilan / Potongan</h3>
<table>
<thead><tr><th>Komponen</th><th>Tipe</th><th>Sumber</th><th class="right">Nominal</th></tr></thead>
<tbody>
<tr><td>Gaji Pokok</td><td>BASE</td><td>Compensation Snapshot</td><td class="right">Rp {{ number_format((float)$payroll->base_salary_snapshot,0,',','.') }}</td></tr>
@foreach($payroll->items as $item)
<tr><td>{{ $item->name }}</td><td>{{ $item->type }}</td><td>{{ $item->source_type ?? '-' }}</td><td class="right">Rp {{ number_format((float)$item->amount,0,',','.') }}</td></tr>
@endforeach
</tbody>
</table>

<table>
<tr><th>Gross Pay</th><td class="right">Rp {{ number_format((float)$payroll->gross_pay,0,',','.') }}</td></tr>
<tr><th>Total Deduction</th><td class="right">Rp {{ number_format((float)$payroll->total_deduction,0,',','.') }}</td></tr>
<tr><th class="net">Net Pay</th><td class="right net">Rp {{ number_format((float)$payroll->net_pay,0,',','.') }}</td></tr>
</table>
</div>
</body>
</html>
