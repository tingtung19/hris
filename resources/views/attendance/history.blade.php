@extends('layouts.app')
@section('title','Riwayat Absensi')
@section('content')
<div class="d-flex justify-content-between mb-3"><h1 class="h3">Riwayat Absensi</h1><form><input type="month" name="month" value="{{ $month }}"><button class="btn btn-outline-primary">Tampilkan</button></form></div>
<div class="card shadow-sm"><div class="card-body"><table class="table"><thead><tr><th>Tanggal</th><th>Clock in</th><th>Clock out</th><th>Status</th><th>Menit kerja</th></tr></thead><tbody>@forelse($attendances as $row)<tr><td>{{ $row->date }}</td><td>{{ $row->clock_in ?? '-' }}</td><td>{{ $row->clock_out ?? '-' }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->work_minutes }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr>@endforelse</tbody></table>{{ $attendances->links() }}</div></div>
@endsection
