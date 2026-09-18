@extends('layouts.app')
@section('title','Notifikasi')
@section('content')
<h1 class="h3 mb-3">Notifikasi</h1><div class="card shadow-sm"><div class="card-body">@forelse($notifications as $notification)<div class="border-bottom py-3 {{ $notification->is_read ? '' : 'fw-semibold' }}"><div>{{ $notification->title }}</div><small class="text-muted">{{ $notification->message }}</small>@if(!$notification->is_read)<form method="POST" action="{{ route('notifications.read',$notification->id) }}" class="mt-1">@csrf<button class="btn btn-sm btn-link p-0">Tandai dibaca</button></form>@endif</div>@empty<p class="text-muted mb-0">Tidak ada notifikasi.</p>@endforelse{{ $notifications->links() }}</div></div>
@endsection
