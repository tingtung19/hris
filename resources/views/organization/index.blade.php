@extends('layouts.app')

@section('title', $config['title'])

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">@foreach($entities as $key=>$entity)<a class="btn btn-sm {{ $key === $slug ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('organization.index',$key) }}">{{ $entity['title'] }}</a>@endforeach<a class="btn btn-sm btn-outline-primary ms-auto" href="{{ route('organization.chart') }}">Organization chart</a></div>
<div class="card shadow-sm"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">{{ $config['title'] }}</h1><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">Tambah</button></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form class="row g-2 mb-3"><div class="col-md-6"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Cari</button></div></form>
<div class="table-responsive"><table class="table"><thead><tr>@foreach($config['fields'] as $field)<th>{{ ucwords(str_replace('_',' ',$field)) }}</th>@endforeach<th></th></tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($config['fields'] as $field)<td>{{ $row->$field ?? '-' }}</td>@endforeach<td class="text-end"><form method="POST" action="{{ route('organization.destroy',[$slug,$row->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">Hapus</button></form></td></tr>@empty<tr><td colspan="{{ count($config['fields']) + 1 }}" class="text-center text-muted">Belum ada data.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}
</div></div>
<div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('organization.store',$slug) }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah {{ $config['title'] }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">@foreach($config['fields'] as $field)<div class="mb-3"><label class="form-label">{{ ucwords(str_replace('_',' ',$field)) }}</label><input name="{{ $field }}" class="form-control"></div>@endforeach</div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
@endsection
