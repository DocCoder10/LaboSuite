@extends('layouts.app')

@section('content')
    <section class="lms-page-head">
        <h2>{{ __('messages.settings_title') }}</h2>
    </section>

    <form method="POST" action="{{ route('settings.update') }}" class="lms-card lms-stack">
        @csrf
        @method('PUT')

        <div class="lms-grid-2">
            <label class="lms-field">
                <span>{{ __('messages.lab_name') }}</span>
                <input name="name" value="{{ old('name', $identity['name'] ?? '') }}" required>
            </label>
            <label class="lms-field">
                <span>{{ __('messages.lab_address') }}</span>
                <input name="address" value="{{ old('address', $identity['address'] ?? '') }}">
            </label>
            <label class="lms-field">
                <span>{{ __('messages.lab_phone') }}</span>
                <input name="phone" value="{{ old('phone', $identity['phone'] ?? '') }}">
            </label>
            <label class="lms-field">
                <span>{{ __('messages.lab_email') }}</span>
                <input name="email" type="email" value="{{ old('email', $identity['email'] ?? '') }}">
            </label>
            <label class="lms-field">
                <span>{{ __('messages.header_note') }}</span>
                <input name="header_note" value="{{ old('header_note', $identity['header_note'] ?? '') }}">
            </label>
            <label class="lms-field">
                <span>{{ __('messages.footer_note') }}</span>
                <input name="footer_note" value="{{ old('footer_note', $identity['footer_note'] ?? '') }}">
            </label>
        </div>

        <label class="lms-checkbox">
            <input type="hidden" name="show_unit_column" value="0">
            <input type="checkbox" name="show_unit_column" value="1" @checked(old('show_unit_column', $layout['show_unit_column'] ?? false))>
            <span>{{ __('messages.show_unit_column') }}</span>
        </label>

        <label class="lms-checkbox">
            <input type="hidden" name="highlight_abnormal" value="0">
            <input type="checkbox" name="highlight_abnormal" value="1" @checked(old('highlight_abnormal', $layout['highlight_abnormal'] ?? true))>
            <span>{{ __('messages.highlight_abnormal') }}</span>
        </label>

        <button class="lms-btn" type="submit">{{ __('messages.save_settings') }}</button>
    </form>
@endsection
