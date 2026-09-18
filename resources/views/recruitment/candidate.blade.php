@extends('layouts.app')
@section('title', 'Kandidat Recruitment')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-1">{{ $candidate->full_name }}</h1><div class="text-muted">{{ $candidate->vacancy_title }} · {{ ucfirst(str_replace('_', ' ', $candidate->stage)) }}</div></div>
    <a class="btn btn-outline-secondary" href="{{ route('recruitment.pipeline', $candidate->vacancy_id) }}">Kembali ke pipeline</a>
</div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row g-3">
    <div class="col-lg-4"><div class="card shadow-sm"><div class="card-body"><h5>Profil</h5><dl class="mb-0"><dt>Email</dt><dd>{{ $candidate->email ?: '-' }}</dd><dt>Telepon</dt><dd>{{ $candidate->phone ?: '-' }}</dd><dt>Tanggal lahir</dt><dd>{{ $candidate->birth_date ?: '-' }}</dd><dt>Sumber</dt><dd>{{ $candidate->source ?: '-' }}</dd><dt>CV</dt><dd>{{ $candidate->cv_path ?: '-' }}</dd></dl></div></div></div>
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3"><div class="card-header">Interview</div><div class="card-body">
            <form method="POST" action="{{ route('recruitment.candidates.interviews.store', $candidate->id) }}" class="row g-2 mb-3">@csrf<select name="type" class="form-select col" required><option value="hr">HR</option><option value="user">User</option><option value="technical">Technical</option></select><input name="schedule_at" type="datetime-local" class="form-control" required><input name="location" class="form-control" placeholder="Lokasi"><textarea name="notes" class="form-control" placeholder="Catatan"></textarea><button class="btn btn-primary">Jadwalkan</button></form>
            @foreach($interviews as $interview)<div class="border rounded p-2 mb-2"><strong>{{ ucfirst($interview->type) }}</strong> · {{ $interview->schedule_at }} · <span class="badge text-bg-{{ $interview->result === 'pass' ? 'success' : ($interview->result === 'fail' ? 'danger' : 'warning') }}">{{ $interview->result }}</span>@if($interview->result === 'pending')<form method="POST" action="{{ route('recruitment.interviews.decide', $interview->id) }}" class="d-inline-flex gap-1 ms-2">@csrf<select name="result" class="form-select form-select-sm"><option value="pass">Pass</option><option value="fail">Fail</option></select><input name="notes" class="form-control form-control-sm" placeholder="Catatan"><button class="btn btn-sm btn-outline-primary">Simpan</button></form>@endif</div>@endforeach
        </div></div>
        <div class="card shadow-sm mb-3"><div class="card-header">Assessment</div><div class="card-body"><form method="POST" action="{{ route('recruitment.candidates.assessments.store', $candidate->id) }}" class="row g-2 mb-3">@csrf<input name="assessment_name" class="form-control" placeholder="Nama assessment" required><input name="score" type="number" min="0" max="100" step="0.01" class="form-control" placeholder="Skor"><input name="notes" class="form-control" placeholder="Catatan"><button class="btn btn-primary">Simpan assessment</button></form>@foreach($assessments as $assessment)<div class="border-bottom py-2">{{ $assessment->assessment_name }} · {{ $assessment->score ?? '-' }} · {{ $assessment->notes ?: '-' }}</div>@endforeach</div></div>
        <div class="card shadow-sm"><div class="card-header">Riwayat Tahap</div><div class="card-body">@foreach($history as $item)<div class="border-bottom py-2">{{ ucfirst(str_replace('_', ' ', $item->stage)) }} · {{ $item->changed_at }} · {{ $item->notes ?: '-' }}</div>@endforeach</div></div>
    </div>
</div>
@endsection
