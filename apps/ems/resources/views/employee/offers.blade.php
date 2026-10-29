@extends('layouts.employee')

@section('title', 'My Agreements · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Contract Offers and Agreements</h1>
            <p class="text-body-secondary mt-1 mb-0">New contracts and changes of assignment HR sends you. Nothing changes until you accept.</p>
        </div>

        <div class="card overflow-hidden">
            <ul class="list-group list-group-flush">
                @forelse ($offers as $offer)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <div>
                            <a class="fw-medium text-reset" href="{{ route('employee.offers.show', $offer) }}">{{ $offer->kind->label() }}: {{ $offer->summary['position'] }}</a>
                            <small class="d-block text-body-secondary">Starts {{ $offer->start_date->format('M j, Y') }}{{ $offer->end_date ? ' · ends '.$offer->end_date->format('M j, Y') : '' }} · sent {{ $offer->created_at->format('M j, Y') }}</small>
                        </div>
                        <span class="status-pill">{{ $offer->status->label() }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-body-secondary">You have no offers or agreements.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
