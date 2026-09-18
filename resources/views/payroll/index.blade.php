@extends('layouts.app')
@section('title','Payroll')
@section('content')
<h1 class="h3 mb-3">Payroll</h1>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="card shadow-sm mb-3"><div class="card-body"><form method="POST" action="{{ route('payroll.periods.store') }}" class="row g-2">@csrf<input name="name" class="form-control col" placeholder="Nama periode" required><input name="start_date" type="date" class="form-control col" required><input name="end_date" type="date" class="form-control col" required><button class="btn btn-primary col">Buat periode</button></form></div></div>
<div class="card shadow-sm"><div class="card-body"><table class="table"><thead><tr><th>Periode</th><th>Tanggal</th><th>Status</th><th></th></tr></thead><tbody>@foreach($periods as $period)<tr><td>{{ $period->name }}</td><td>{{ $period->start_date }} - {{ $period->end_date }}</td><td>{{ ucfirst($period->status) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.periods.show',$period->id) }}">Detail</a></td></tr>@endforeach</tbody></table>{{ $periods->links() }}</div></div>
@endsection
