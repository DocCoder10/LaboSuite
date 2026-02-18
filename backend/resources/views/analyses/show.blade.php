@extends('layouts.app')

@section('content')
    <section class="lms-page-head">
        <h2>{{ __('messages.preview_title') }}</h2>
        <div class="lms-inline-actions">
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.index') }}">{{ __('messages.back_to_list') }}</a>
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.create') }}">{{ __('messages.open_new') }}</a>
            <a class="lms-btn" href="{{ route('analyses.print', $analysis) }}" target="_blank">{{ __('messages.print') }}</a>
        </div>
    </section>

    @include('analyses.partials.report', ['analysis' => $analysis, 'groupedResults' => $groupedResults, 'layout' => $layout, 'identity' => $identity])
@endsection
