@extends('layouts.app')

@section('content')
    @php
        $openAddFieldModal = old('section') === 'patient' && filled(old('patient_new.label'));
    @endphp

    <section class="lms-page-head">
        <h2>{{ __('messages.settings_title') }}</h2>
    </section>

    <section
        class="lms-settings-shell"
        data-settings-page
        data-label-active="{{ __('messages.active') }}"
        data-label-inactive="{{ __('messages.inactive') }}"
        data-label-marked-delete="{{ __('messages.field_marked_delete') }}"
        data-label-delete="{{ __('messages.delete') }}"
        data-label-undo-delete="{{ __('messages.undo_delete') }}"
        data-label-confirm-delete-field="{{ __('messages.confirm_delete_field') }}"
    >
        <nav class="lms-card lms-settings-nav">
            <a class="lms-settings-nav-link {{ $activeSection === 'lab' ? 'is-active' : '' }}" href="{{ route('settings.edit', ['section' => 'lab']) }}">
                {{ __('messages.settings_nav_lab') }}
            </a>
            <a class="lms-settings-nav-link {{ $activeSection === 'pdf' ? 'is-active' : '' }}" href="{{ route('settings.edit', ['section' => 'pdf']) }}">
                {{ __('messages.settings_nav_pdf') }}
            </a>
            <a class="lms-settings-nav-link {{ $activeSection === 'patient' ? 'is-active' : '' }}" href="{{ route('settings.edit', ['section' => 'patient']) }}">
                {{ __('messages.settings_nav_patient') }}
            </a>
        </nav>

        @if ($activeSection === 'lab')
            <form method="POST" action="{{ route('settings.update') }}" class="lms-card lms-stack">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="lab">

                <h3>{{ __('messages.settings_nav_lab') }}</h3>

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

                <button class="lms-btn" type="submit">{{ __('messages.save_settings') }}</button>
            </form>
        @endif

        @if ($activeSection === 'pdf')
            <form method="POST" action="{{ route('settings.update') }}" class="lms-card lms-stack">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="pdf">

                <h3>{{ __('messages.settings_nav_pdf') }}</h3>

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
        @endif

        @if ($activeSection === 'patient')
            <form method="POST" action="{{ route('settings.update') }}" class="lms-card lms-stack">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="patient">

                <div class="lms-settings-head">
                    <div class="lms-stack">
                        <h3>{{ __('messages.settings_nav_patient') }}</h3>
                        <p class="lms-muted">{{ __('messages.patient_fields_section_help') }}</p>
                    </div>
                    <button type="button" class="lms-btn lms-btn-soft" data-modal-open="modal-add-patient-field">{{ __('messages.patient_add_field') }}</button>
                </div>

                <label class="lms-checkbox">
                    <input type="hidden" name="patient_identifier_required" value="0">
                    <input type="checkbox" name="patient_identifier_required" value="1" @checked(old('patient_identifier_required', $patientForm['identifier_required'] ?? false))>
                    <span>{{ __('messages.patient_identifier_required') }}</span>
                </label>

                <div class="lms-table-wrap">
                    <table class="lms-table lms-table-patient-fields">
                        <thead>
                            <tr>
                                <th>{{ __('messages.parameter') }}</th>
                                <th>{{ __('messages.name') }}</th>
                                <th>{{ __('messages.value_type') }}</th>
                                <th>{{ __('messages.status') }}</th>
                                <th>{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($patientForm['fields'] as $index => $field)
                                @php
                                    $inputPrefix = 'patient_fields.'.$index;
                                    $isCustom = ! ($field['built_in'] ?? false);
                                    $isLocked = (bool) ($field['locked'] ?? false);
                                    $fieldType = $field['type'] ?? 'text';
                                    $isActive = (bool) old($inputPrefix.'.active', $field['active'] ?? true);
                                    $isDeleted = (bool) old($inputPrefix.'.delete', false);
                                    $resolvedType = old($inputPrefix.'.type', $fieldType);
                                    $resolvedLabel = old($inputPrefix.'.label', $field['label']);
                                    $statusText = $isDeleted
                                        ? __('messages.field_marked_delete')
                                        : ($isActive ? __('messages.active') : __('messages.inactive'));
                                @endphp
                                <tr
                                    data-field-row
                                    data-field-index="{{ $index }}"
                                    data-custom="{{ $isCustom ? '1' : '0' }}"
                                    data-locked="{{ $isLocked ? '1' : '0' }}"
                                    data-active="{{ $isActive ? '1' : '0' }}"
                                    data-deleted="{{ $isDeleted ? '1' : '0' }}"
                                    class="{{ $isDeleted ? 'is-marked-delete' : '' }}"
                                >
                                    <td>
                                        <strong>{{ $field['key'] }}</strong>
                                        <input type="hidden" name="patient_fields[{{ $index }}][key]" value="{{ $field['key'] }}">
                                        <input type="hidden" name="patient_fields[{{ $index }}][label]" value="{{ $resolvedLabel }}" data-field-input-label>
                                        <input type="hidden" name="patient_fields[{{ $index }}][type]" value="{{ $resolvedType }}" data-field-input-type>
                                        <input type="hidden" name="patient_fields[{{ $index }}][active]" value="{{ $isActive ? '1' : '0' }}" data-field-input-active>
                                        <input type="hidden" name="patient_fields[{{ $index }}][delete]" value="{{ $isDeleted ? '1' : '0' }}" data-field-input-delete>
                                    </td>
                                    <td>
                                        <span data-field-label-display>{{ $resolvedLabel }}</span>
                                    </td>
                                    <td>
                                        <span data-field-type-display data-type-value="{{ $resolvedType }}">
                                            {{ $resolvedType === 'number' ? __('messages.value_type_number') : __('messages.value_type_text') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="lms-field-chip {{ $isDeleted ? 'is-delete' : ($isActive ? 'is-active' : 'is-inactive') }}" data-field-status-display>
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="lms-table-actions">
                                            @if (! $isLocked)
                                                <button
                                                    type="button"
                                                    class="lms-btn lms-btn-soft"
                                                    data-field-edit
                                                    data-row-index="{{ $index }}"
                                                >
                                                    {{ __('messages.edit') }}
                                                </button>
                                            @endif

                                            @if ($isCustom)
                                                <button
                                                    type="button"
                                                    class="lms-btn lms-btn-soft {{ $isDeleted ? 'is-toggled' : '' }}"
                                                    data-field-delete
                                                    data-row-index="{{ $index }}"
                                                >
                                                    {{ $isDeleted ? __('messages.undo_delete') : __('messages.delete') }}
                                                </button>
                                            @else
                                                <span class="lms-muted">{{ __('messages.not_applicable') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button class="lms-btn" type="submit">{{ __('messages.save_settings') }}</button>

                <dialog id="modal-add-patient-field" class="lms-modal" @if ($openAddFieldModal) data-open-on-load="1" @endif>
                    <article class="lms-modal-card lms-stack">
                        <header class="lms-modal-head">
                            <h4>{{ __('messages.patient_add_field') }}</h4>
                            <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
                        </header>

                        <div class="lms-grid-2">
                            <label class="lms-field">
                                <span>{{ __('messages.name') }}</span>
                                <input type="text" name="patient_new[label]" value="{{ old('patient_new.label') }}">
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.value_type') }}</span>
                                <select name="patient_new[type]">
                                    <option value="text" @selected(old('patient_new.type', 'text') === 'text')>{{ __('messages.value_type_text') }}</option>
                                    <option value="number" @selected(old('patient_new.type') === 'number')>{{ __('messages.value_type_number') }}</option>
                                </select>
                            </label>
                        </div>

                        <label class="lms-checkbox">
                            <input type="hidden" name="patient_new[active]" value="0">
                            <input type="checkbox" name="patient_new[active]" value="1" @checked(old('patient_new.active', true))>
                            <span>{{ __('messages.active') }}</span>
                        </label>

                        <div class="lms-inline-actions lms-wrap-actions">
                            <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                            <button class="lms-btn" type="submit">{{ __('messages.save_settings') }}</button>
                        </div>
                    </article>
                </dialog>

                <dialog id="modal-edit-patient-field" class="lms-modal">
                    <article class="lms-modal-card lms-stack">
                        <header class="lms-modal-head">
                            <h4>{{ __('messages.patient_edit_field') }}</h4>
                            <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
                        </header>

                        <input type="hidden" data-edit-field-index>

                        <div class="lms-grid-2">
                            <label class="lms-field">
                                <span>{{ __('messages.name') }}</span>
                                <input type="text" data-edit-field-label>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.value_type') }}</span>
                                <select data-edit-field-type>
                                    <option value="text">{{ __('messages.value_type_text') }}</option>
                                    <option value="number">{{ __('messages.value_type_number') }}</option>
                                </select>
                            </label>
                        </div>

                        <label class="lms-checkbox">
                            <input type="checkbox" value="1" data-edit-field-active>
                            <span>{{ __('messages.active') }}</span>
                        </label>

                        <div class="lms-inline-actions lms-wrap-actions">
                            <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                            <button type="button" class="lms-btn" data-edit-field-save>{{ __('messages.apply_changes') }}</button>
                        </div>
                    </article>
                </dialog>
            </form>
        @endif
    </section>
@endsection
