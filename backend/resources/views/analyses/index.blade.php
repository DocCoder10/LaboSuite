@extends('layouts.app')

@section('content')
    <section class="lms-page-head lms-sticky-head">
        <h2>{{ __('messages.analysis_list_title') }}</h2>
        <a class="lms-btn" href="{{ route('analyses.create') }}">{{ __('messages.new_analysis') }}</a>
    </section>

    <section class="lms-card">
        <form method="GET" action="{{ route('analyses.index') }}" class="lms-list-filters" data-analyses-filters>
            <label class="lms-field lms-filter-search">
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

            <label class="lms-field lms-filter-period">
                <span>{{ __('messages.period') }}</span>
                <select name="period" data-auto-submit>
                    <option value="all" @selected($period === 'all')>{{ __('messages.period_all') }}</option>
                    <option value="today" @selected($period === 'today')>{{ __('messages.period_today') }}</option>
                    <option value="7_days" @selected($period === '7_days')>{{ __('messages.period_7_days') }}</option>
                    <option value="30_days" @selected($period === '30_days')>{{ __('messages.period_30_days') }}</option>
                </select>
            </label>

            <label class="lms-field lms-filter-page">
                <span>{{ __('messages.items_per_page') }}</span>
                <select class="lms-control-page" name="per_page" data-auto-submit>
                    <option value="15" @selected($perPage === 15)>15</option>
                    <option value="20" @selected($perPage === 20)>20</option>
                </select>
            </label>

            <label class="lms-field lms-filter-sort">
                <span>{{ __('messages.sort_date') }}</span>
                <select class="lms-control-sort" name="direction" data-auto-submit aria-label="{{ __('messages.sort_date') }}">
                    <option value="desc" @selected($direction === 'desc')>{{ __('messages.sort_recent') }}</option>
                    <option value="asc" @selected($direction === 'asc')>{{ __('messages.sort_oldest') }}</option>
                </select>
            </label>

            <input type="hidden" name="sort" value="{{ $sort }}">
        </form>
    </section>

    <section class="lms-results-shell" data-analyses-results data-loading="0">
        @include('analyses.partials.list-results')
    </section>
@endsection
