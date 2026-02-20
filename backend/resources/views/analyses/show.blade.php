@extends('layouts.app')

@section('content')
    <section class="lms-page-head">
        <h2>{{ __('messages.preview_title') }}</h2>
        <div class="lms-inline-actions">
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.index', $listQuery) }}">{{ __('messages.back_to_list') }}</a>
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.edit', ['analysis' => $analysis] + $listQuery) }}">{{ __('messages.edit') }}</a>
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.create') }}">{{ __('messages.open_new') }}</a>
            <a class="lms-btn" href="{{ route('analyses.print', ['analysis' => $analysis] + $listQuery) }}" target="_blank" rel="noopener">{{ __('messages.print') }}</a>
        </div>
    </section>

    <section class="lms-card">
        <p class="lms-muted">
            {{ __('messages.created_at') }}: {{ optional($analysis->created_at)->format('Y-m-d H:i') }}
            |
            {{ __('messages.updated_at') }}: {{ optional($analysis->updated_at)->format('Y-m-d H:i') }}
        </p>
        <p class="lms-muted">{{ __('messages.status_locked_until_edit') }}</p>
    </section>

    @include('analyses.partials.report', ['analysis' => $analysis, 'groupedResults' => $groupedResults, 'layout' => $layout, 'identity' => $identity])
@endsection
