@extends('layouts.app')

@section('content')
    <section class="lms-page-head">
        <h2>{{ __('messages.analysis_list_title') }}</h2>
        <a class="lms-btn" href="{{ route('analyses.create') }}">{{ __('messages.new_analysis') }}</a>
    </section>

    @if ($analyses->isEmpty())
        <div class="lms-card">{{ __('messages.empty_analyses') }}</div>
    @else
        <div class="lms-card lms-table-wrap">
            <table class="lms-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.analysis_number') }}</th>
                        <th>{{ __('messages.patient') }}</th>
                        <th>{{ __('messages.analysis_date') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analyses as $analysis)
                        <tr>
                            <td>{{ $analysis->analysis_number }}</td>
                            <td>{{ $analysis->patient?->full_name ?? '-' }}</td>
                            <td>{{ optional($analysis->analysis_date)->format('Y-m-d') }}</td>
                            <td class="lms-actions-cell">
                                <a class="lms-btn lms-btn-soft" href="{{ route('analyses.show', $analysis) }}">{{ __('messages.view') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $analyses->links() }}
        </div>
    @endif
@endsection
