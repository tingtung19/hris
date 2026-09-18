@extends('layouts.app')
@section('title','Audit Log')
@section('content')
<h1 class="h3 mb-3">Audit Log</h1><div class="card shadow-sm"><div class="card-body"><table class="table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Modul</th><th>Record</th><th>Deskripsi</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->action }}</td><td>{{ $log->module }}</td><td>{{ $log->record_id ?? '-' }}</td><td>{{ $log->description ?? '-' }}</td></tr>@endforeach</tbody></table>{{ $logs->links() }}</div></div>
@endsection
