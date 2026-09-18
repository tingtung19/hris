@extends('layouts.app')
@section('title','Slip Gaji')
@section('content')
<div class="card shadow-sm"><div class="card-body"><h1 class="h4">Slip Gaji - {{ $payroll->period_name }}</h1><p>{{ $payroll->employee_number }} - {{ $payroll->first_name }} {{ $payroll->last_name }}</p><hr><div class="row"><div class="col">Gaji kotor<br><strong>Rp {{ number_format($payroll->gross_salary,0,',','.') }}</strong></div><div class="col">Potongan<br><strong>Rp {{ number_format($payroll->total_deduction,0,',','.') }}</strong></div><div class="col">Gaji bersih<br><strong>Rp {{ number_format($payroll->net_salary,0,',','.') }}</strong></div></div></div></div>
@endsection
