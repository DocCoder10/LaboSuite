@extends('layouts.app')

@section('content')
    @php
        $totalAnalyses = \App\Models\LabAnalysis::query()->count();
        $analysesLast7Days = \App\Models\LabAnalysis::query()
            ->where('updated_at', '>=', now()->subDays(6)->startOfDay())
            ->count();
        $analysesToday = \App\Models\LabAnalysis::query()
            ->whereDate('updated_at', now()->toDateString())
            ->count();
        $pendingAnalyses = \App\Models\LabAnalysis::query()
            ->where('status', '!=', 'final')
            ->count();
    @endphp

    <section class="lms-page-head">
        <h2>{{ __('messages.analysis_list_title') }}</h2>
        <x-ui.button :href="route('analyses.create')" icon="plus">{{ __('messages.new_analysis') }}</x-ui.button>
    </section>

    <section class="lms-kpi-grid" aria-label="Indicateurs">
        <x-ui.kpi-card
            :title="__('messages.nav_dashboard')"
            :value="$analysesToday"
            :subtext="'+ '.($analysesToday).' aujourd\'hui'"
            icon="flask"
            tone="blue"
        />
        <x-ui.kpi-card
            title="Analyses 7 jours"
            :value="$analysesLast7Days"
            :subtext="'+ '.$analysesLast7Days.' cette semaine'"
            icon="calendar"
            tone="violet"
        />
        <x-ui.kpi-card
            title="Analyses totales"
            :value="$totalAnalyses"
            :subtext="$totalAnalyses.' cumulees'"
            icon="clipboard"
            tone="teal"
        />
        <x-ui.kpi-card
            title="En attente"
            :value="$pendingAnalyses"
            :subtext="$pendingAnalyses > 0 ? 'A traiter' : 'Aucune analyse en attente'"
            icon="clock"
            tone="orange"
        />
    </section>

    <section class="lms-card lms-toolbar-card">
        <header class="lms-toolbar-card-head">
            <h3>{{ __('messages.analysis_list_title') }}</h3>
            <x-ui.button :href="route('analyses.create')" icon="plus">{{ __('messages.new_analysis') }}</x-ui.button>
        </header>

        <form method="GET" action="{{ route('analyses.index') }}" class="lms-list-filters" data-analyses-filters>
            <label class="lms-field lms-filter-search">
                <span>{{ __('messages.quick_search') }}</span>
                <x-ui.input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="{{ __('messages.search_patient_or_number') }}"
                    data-analyses-search
                    autocomplete="off"
                />
            </label>

            <label class="lms-field lms-filter-period">
                <span>{{ __('messages.period') }}</span>
                <x-ui.select name="period" data-auto-submit>
                    <option value="all" @selected($period === 'all')>{{ __('messages.period_all') }}</option>
                    <option value="today" @selected($period === 'today')>{{ __('messages.period_today') }}</option>
                    <option value="7_days" @selected($period === '7_days')>{{ __('messages.period_7_days') }}</option>
                    <option value="30_days" @selected($period === '30_days')>{{ __('messages.period_30_days') }}</option>
                </x-ui.select>
            </label>

            <label class="lms-field lms-filter-page">
                <span>{{ __('messages.items_per_page') }}</span>
                <x-ui.select class="lms-control-page" name="per_page" data-auto-submit>
                    <option value="15" @selected($perPage === 15)>15</option>
                    <option value="20" @selected($perPage === 20)>20</option>
                </x-ui.select>
            </label>

            <label class="lms-field lms-filter-sort">
                <span>{{ __('messages.sort_date') }}</span>
                <x-ui.select class="lms-control-sort" name="direction" data-auto-submit aria-label="{{ __('messages.sort_date') }}">
                    <option value="desc" @selected($direction === 'desc')>{{ __('messages.sort_recent') }}</option>
                    <option value="asc" @selected($direction === 'asc')>{{ __('messages.sort_oldest') }}</option>
                </x-ui.select>
            </label>

            <input type="hidden" name="sort" value="{{ $sort }}">
        </form>

        <section class="lms-results-shell" data-analyses-results data-loading="0">
            @include('analyses.partials.list-results')
        </section>
    </section>
@endsection
