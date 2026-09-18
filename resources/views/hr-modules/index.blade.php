@extends('layouts.app')

@section('title', $config['title'])
@section('content')
    <h1 class="h3 mb-3">{{ $config['title'] }}</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route($module . '.store') }}" class="row g-2">
                @csrf
                @foreach ($config['columns'] as $column)
                    <div class="col-md-3">
                        <input
                            name="{{ $column }}"
                            class="form-control"
                            placeholder="{{ ucwords(str_replace('_', ' ', $column)) }}"
                        >
                    </div>
                @endforeach
                <button class="btn btn-primary col-md-2">Tambah</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            @foreach ($config['columns'] as $column)
                                <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($config['columns'] as $column)
                                    <td>{{ $row->$column ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($config['columns']) }}" class="text-center text-muted">
                                    Belum ada data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    </div>
@endsection
