@extends('layouts.app')

@section('content')
    @php
        $nextDirection = $direction === 'asc' ? 'desc' : 'asc';
        $sortIndicator = $direction === 'asc' ? 'ASC' : 'DESC';
        $sortUrl = request()->fullUrlWithQuery([
            'sort' => 'date',
            'direction' => $nextDirection,
            'page' => 1,
        ]);
    @endphp

    <section class="lms-page-head lms-sticky-head">
        <h2>{{ __('messages.analysis_list_title') }}</h2>
        <a class="lms-btn" href="{{ route('analyses.create') }}">{{ __('messages.new_analysis') }}</a>
    </section>

    <section class="lms-card">
        <form method="GET" action="{{ route('analyses.index') }}" class="lms-list-filters" data-analyses-filters>
            <label class="lms-field">
                <span>{{ __('messages.quick_search') }}</span>
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="{{ __('messages.search_patient_or_number') }}"
                    data-analyses-search
                    autocomplete="off"
                >
            </label>

            <label class="lms-field">
                <span>{{ __('messages.period') }}</span>
                <select name="period" data-auto-submit>
                    <option value="all" @selected($period === 'all')>{{ __('messages.period_all') }}</option>
                    <option value="today" @selected($period === 'today')>{{ __('messages.period_today') }}</option>
                    <option value="7_days" @selected($period === '7_days')>{{ __('messages.period_7_days') }}</option>
                    <option value="30_days" @selected($period === '30_days')>{{ __('messages.period_30_days') }}</option>
                </select>
            </label>

            <label class="lms-field">
                <span>{{ __('messages.items_per_page') }}</span>
                <select name="per_page" data-auto-submit>
                    <option value="15" @selected($perPage === 15)>15</option>
                    <option value="20" @selected($perPage === 20)>20</option>
                </select>
            </label>

            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        </form>
    </section>

    @if ($analyses->isEmpty())
        <div class="lms-card">{{ __('messages.empty_analyses') }}</div>
    @else
        <div class="lms-card lms-table-wrap lms-table-wrap-list">
            <table class="lms-table lms-table-list">
                <thead>
                    <tr>
                        <th>{{ __('messages.analysis_number') }}</th>
                        <th>{{ __('messages.patient') }}</th>
                        <th>{{ __('messages.analysis_date') }}</th>
                        <th>
                            <a class="lms-sort-link" href="{{ $sortUrl }}">
                                {{ __('messages.updated_at') }}
                                @if ($sort === 'date')
                                    <span>{{ $sortIndicator }}</span>
                                @endif
                            </a>
                        </th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analyses as $analysis)
                        <tr>
                            <td>{{ $analysis->analysis_number }}</td>
                            <td>
                                <div>{{ $analysis->patient?->full_name ?? '-' }}</div>
                                <small class="lms-muted">{{ $analysis->patient?->identifier ?? '-' }}</small>
                            </td>
                            <td>{{ optional($analysis->analysis_date)->format('Y-m-d') }}</td>
                            <td>{{ optional($analysis->updated_at)->format('Y-m-d H:i') }}</td>
                            <td class="lms-actions-cell">
                                <div class="lms-table-actions">
                                    <a class="lms-btn lms-btn-soft" href="{{ route('analyses.show', ['analysis' => $analysis] + $listQuery) }}">{{ __('messages.view') }}</a>
                                    <a class="lms-btn lms-btn-soft" href="{{ route('analyses.print', ['analysis' => $analysis] + $listQuery) }}" target="_blank" rel="noopener">{{ __('messages.print') }}</a>
                                    <a class="lms-btn lms-btn-soft" href="{{ route('analyses.edit', ['analysis' => $analysis] + $listQuery) }}">{{ __('messages.edit') }}</a>
                                    <form method="POST" action="{{ route('analyses.destroy', ['analysis' => $analysis] + $listQuery) }}" data-analysis-delete-form data-delete-message="{{ __('messages.confirm_delete_analysis_number', ['number' => $analysis->analysis_number]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="lms-card lms-list-footer">
            <p class="lms-muted">{{ __('messages.total_results', ['count' => $analyses->total()]) }}</p>
            {{ $analyses->links() }}
        </div>
    @endif
@endsection
