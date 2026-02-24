@extends('layouts.app')

@section('content')
    @php
        $openAddFieldModal = old('section') === 'patient' && filled(old('patient_new.label'));
        $tabItems = [
            [
                'href' => route('settings.edit', ['section' => 'lab']),
                'label' => __('messages.settings_nav_lab'),
                'active' => $activeSection === 'lab',
            ],
            [
                'href' => route('settings.edit', ['section' => 'pdf']),
                'label' => __('messages.settings_nav_pdf'),
                'active' => $activeSection === 'pdf',
            ],
            [
                'href' => route('settings.edit', ['section' => 'patient']),
                'label' => __('messages.settings_nav_patient'),
                'active' => $activeSection === 'patient',
            ],
        ];

        $appFontFamilies = $uiOptions['app_font_families'] ?? [];
        $uiFontSizeLevels = $uiOptions['ui_font_size_levels'] ?? [];
        $labelFontWeights = $uiOptions['label_font_weights'] ?? [];
        $labelTextTransforms = $uiOptions['label_text_transforms'] ?? [];
        $motionProfiles = $uiOptions['motion_profiles'] ?? [];
        $reportFontFamilies = $uiOptions['report_font_families'] ?? [];

        $appFontStacks = [
            'legacy' => "'Inter', 'IBM Plex Sans', 'Segoe UI', 'Noto Sans', 'Helvetica Neue', Arial, sans-serif",
            'inter' => "'Inter', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
            'roboto' => "'Roboto', 'Arial', 'Noto Sans', 'Helvetica Neue', sans-serif",
            'medical' => "'Source Sans 3', 'Trebuchet MS', Verdana, 'Noto Sans', sans-serif",
            'robotic' => "'Orbitron', 'Rajdhani', 'Consolas', 'Lucida Console', 'Courier New', monospace",
            'mono' => "'JetBrains Mono', 'Fira Code', 'Cascadia Mono', 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
            'serif' => "'Georgia', 'Times New Roman', 'Liberation Serif', serif",
        ];
        $reportFontStacks = [
            'medical' => "'IBM Plex Sans', 'Source Sans 3', 'Segoe UI', 'Noto Sans', sans-serif",
            'legacy' => "'IBM Plex Sans', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
            'inter' => "'Inter', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
            'roboto' => "'Roboto', 'Arial', 'Noto Sans', 'Helvetica Neue', sans-serif",
            'robotic' => "'Orbitron', 'Rajdhani', 'Consolas', 'Lucida Console', 'Courier New', monospace",
            'mono' => "'JetBrains Mono', 'Fira Code', 'Cascadia Mono', 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
            'serif' => "'Georgia', 'Times New Roman', 'Liberation Serif', serif",
        ];

        $currentAppFont = (string) old('app_font_family', $uiAppearance['app_font_family'] ?? 'legacy');
        $currentReportFont = (string) old('report_font_family', $layout['report_font_family'] ?? 'medical');
        $currentLabelSpacing = round((float) old('label_letter_spacing_em', $uiAppearance['label_letter_spacing_em'] ?? 0.01), 2);
        $spacingPresets = [0.00, 0.02, 0.05];
        $isSpacingCustom = ! in_array($currentLabelSpacing, $spacingPresets, true);
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
        data-label-reset-confirm="{{ __('messages.settings_reset_confirm') }}"
        data-header-rule-left="{{ __('messages.header_rule_left') }}"
        data-header-rule-right="{{ __('messages.header_rule_right') }}"
        data-header-rule-center="{{ __('messages.header_rule_center') }}"
        data-pdf-structure-ok="{{ __('messages.settings_pdf_structure_ok') }}"
        data-pdf-auto-adjusted="{{ __('messages.settings_pdf_structure_auto_adjusted') }}"
    >
        <x-ui.tabs :items="$tabItems" class="lms-card" />

        @if ($activeSection === 'lab')
            <form method="POST" action="{{ route('settings.update') }}" class="lms-card lms-stack" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="lab">

                <div class="lms-section-title">
                    <h3>{{ __('messages.settings_nav_lab') }}</h3>
                    <x-ui.tooltip :text="__('messages.settings_lab_tooltip')">
                        <button type="button" class="lms-help-dot" aria-label="{{ __('messages.help') }}">!</button>
                    </x-ui.tooltip>
                </div>

                <div class="lms-settings-cluster">
                    <article class="lms-settings-panel lms-stack">
                        <h4>{{ __('messages.settings_lab_info_block') }}</h4>
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
                    </article>

                    <article class="lms-settings-panel lms-stack">
                        <div class="lms-settings-head">
                            <div class="lms-stack">
                                <h4>{{ __('messages.settings_header_block') }}</h4>
                                <p class="lms-muted">{{ __('messages.settings_header_block_help') }}</p>
                            </div>
                        </div>

                        <div class="lms-grid-2">
                            <label class="lms-field">
                                <span>{{ __('messages.header_info_position') }}</span>
                                <select name="header_info_position" data-header-info-position>
                                    <option value="left" @selected(old('header_info_position', $identity['header_info_position'] ?? 'center') === 'left')>{{ __('messages.position_left') }}</option>
                                    <option value="center" @selected(old('header_info_position', $identity['header_info_position'] ?? 'center') === 'center')>{{ __('messages.position_center') }}</option>
                                    <option value="right" @selected(old('header_info_position', $identity['header_info_position'] ?? 'center') === 'right')>{{ __('messages.position_right') }}</option>
                                </select>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.header_logo_mode') }}</span>
                                <select name="header_logo_mode" data-header-logo-mode>
                                    <option value="single_left" @selected(old('header_logo_mode', $identity['header_logo_mode'] ?? 'single_left') === 'single_left')>{{ __('messages.logo_mode_single_left') }}</option>
                                    <option value="single_right" @selected(old('header_logo_mode', $identity['header_logo_mode'] ?? 'single_left') === 'single_right')>{{ __('messages.logo_mode_single_right') }}</option>
                                    <option value="both_distinct" @selected(old('header_logo_mode', $identity['header_logo_mode'] ?? 'single_left') === 'both_distinct')>{{ __('messages.logo_mode_both_distinct') }}</option>
                                    <option value="both_same" @selected(old('header_logo_mode', $identity['header_logo_mode'] ?? 'single_left') === 'both_same')>{{ __('messages.logo_mode_both_same') }}</option>
                                </select>
                            </label>
                        </div>

                        <div class="lms-grid-3">
                            <label class="lms-field">
                                <span>{{ __('messages.header_logo_size_px') }}</span>
                                <input
                                    type="number"
                                    min="96"
                                    max="240"
                                    step="1"
                                    name="header_logo_size_px"
                                    value="{{ old('header_logo_size_px', $identity['header_logo_size_px'] ?? 170) }}"
                                >
                            </label>
                            @php
                                $offsetPresets = [-16, -8, 0, 8, 16];
                                $selectedLeftOffset = (int) old('header_logo_offset_x_left', $identity['header_logo_offset_x_left'] ?? 0);
                                $selectedRightOffset = (int) old('header_logo_offset_x_right', $identity['header_logo_offset_x_right'] ?? 0);
                            @endphp
                            <div class="lms-field">
                                <span>{{ __('messages.header_logo_offset_x_left') }}</span>
                                <div class="lms-offset-preset-group">
                                    @foreach ($offsetPresets as $offsetValue)
                                        <label class="lms-offset-preset">
                                            <input
                                                type="radio"
                                                name="header_logo_offset_x_left"
                                                value="{{ $offsetValue }}"
                                                @checked($selectedLeftOffset === $offsetValue)
                                            >
                                            <span>{{ $offsetValue > 0 ? '+'.$offsetValue : $offsetValue }}px</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="lms-field">
                                <span>{{ __('messages.header_logo_offset_x_right') }}</span>
                                <div class="lms-offset-preset-group">
                                    @foreach ($offsetPresets as $offsetValue)
                                        <label class="lms-offset-preset">
                                            <input
                                                type="radio"
                                                name="header_logo_offset_x_right"
                                                value="{{ $offsetValue }}"
                                                @checked($selectedRightOffset === $offsetValue)
                                            >
                                            <span>{{ $offsetValue > 0 ? '+'.$offsetValue : $offsetValue }}px</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <p class="lms-muted" data-header-layout-rule></p>

                        <div class="lms-grid-2">
                            @php
                                $leftLogoPath = old('logo_left_path', $identity['logo_left_path'] ?? '');
                                $rightLogoPath = old('logo_right_path', $identity['logo_right_path'] ?? '');
                                $leftLogoUrl = ! empty($leftLogoPath)
                                    ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $leftLogoPath)
                                    : '';
                                $rightLogoUrl = ! empty($rightLogoPath)
                                    ? \Illuminate\Support\Facades\Storage::disk('public')->url((string) $rightLogoPath)
                                    : '';
                            @endphp

                            <div class="lms-logo-upload">
                                <h5>{{ __('messages.logo_left') }}</h5>
                                <img
                                    class="lms-logo-preview {{ $leftLogoUrl === '' ? 'is-hidden' : '' }}"
                                    src="{{ $leftLogoUrl }}"
                                    alt="{{ __('messages.logo_left') }}"
                                    data-logo-preview="left"
                                >
                                <input type="hidden" name="remove_logo_left" value="0" data-logo-remove-input="left">
                                <div class="lms-logo-actions">
                                    <button type="button" class="lms-btn lms-btn-upload" data-logo-upload-trigger="left">
                                        {{ __('messages.upload_logo') }}
                                    </button>
                                    <button type="button" class="lms-btn lms-btn-danger lms-logo-delete-btn" data-logo-delete-btn="left">
                                        {{ __('messages.delete') }}
                                    </button>
                                </div>
                                <input type="file" name="logo_left" accept="image/*" data-logo-input="left" class="lms-visually-hidden">
                            </div>

                            <div class="lms-logo-upload">
                                <h5>{{ __('messages.logo_right') }}</h5>
                                <img
                                    class="lms-logo-preview {{ $rightLogoUrl === '' ? 'is-hidden' : '' }}"
                                    src="{{ $rightLogoUrl }}"
                                    alt="{{ __('messages.logo_right') }}"
                                    data-logo-preview="right"
                                >
                                <input type="hidden" name="remove_logo_right" value="0" data-logo-remove-input="right">
                                <div class="lms-logo-actions">
                                    <button type="button" class="lms-btn lms-btn-upload" data-logo-upload-trigger="right">
                                        {{ __('messages.upload_logo') }}
                                    </button>
                                    <button type="button" class="lms-btn lms-btn-danger lms-logo-delete-btn" data-logo-delete-btn="right">
                                        {{ __('messages.delete') }}
                                    </button>
                                </div>
                                <input type="file" name="logo_right" accept="image/*" data-logo-input="right" class="lms-visually-hidden">
                            </div>
                        </div>

                        <p class="lms-muted">{{ __('messages.logo_upload_hint') }}</p>
                    </article>

                    <article class="lms-settings-panel lms-stack" data-ui-pref-root>
                        <div class="lms-settings-head">
                            <div class="lms-stack">
                                <h4>{{ __('messages.settings_style_block') }}</h4>
                                <p class="lms-muted">{{ __('messages.settings_style_block_help') }}</p>
                            </div>
                            <x-ui.tooltip :text="__('messages.settings_style_tooltip')">
                                <button type="button" class="lms-help-dot" aria-label="{{ __('messages.help') }}">!</button>
                            </x-ui.tooltip>
                        </div>

                        <div class="lms-grid-3">
                            <label class="lms-field">
                                <span>{{ __('messages.settings_font_family') }}</span>
                                <select name="app_font_family" data-app-font-select>
                                    @foreach ($appFontFamilies as $fontKey)
                                        @php
                                            $fontStack = $appFontStacks[$fontKey] ?? $appFontStacks['legacy'];
                                        @endphp
                                        <option
                                            value="{{ $fontKey }}"
                                            data-font-stack="{{ $fontStack }}"
                                            @selected($currentAppFont === $fontKey)
                                        >
                                            {{ __('messages.settings_font_family_'.$fontKey) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.settings_ui_font_scale') }}</span>
                                <select name="ui_font_size_level">
                                    @foreach ($uiFontSizeLevels as $sizeLevel)
                                        <option value="{{ $sizeLevel }}" @selected(old('ui_font_size_level', $uiAppearance['ui_font_size_level'] ?? 'standard') === $sizeLevel)>
                                            {{ __('messages.settings_ui_font_scale_'.$sizeLevel) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.settings_motion_profile') }}</span>
                                <select name="motion_profile">
                                    @foreach ($motionProfiles as $motion)
                                        <option value="{{ $motion }}" @selected(old('motion_profile', $uiAppearance['motion_profile'] ?? 'soft') === $motion)>
                                            {{ __('messages.settings_motion_profile_'.$motion) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="lms-font-preview" data-font-preview-app style="font-family: {{ $appFontStacks[$currentAppFont] ?? $appFontStacks['legacy'] }};">
                            <p class="lms-font-preview-title">{{ __('messages.font_preview_title_app') }}</p>
                            <p class="lms-font-preview-sample">{{ __('messages.font_preview_sample') }}</p>
                        </div>

                        <div class="lms-grid-3">
                            <label class="lms-field">
                                <span>{{ __('messages.settings_label_font_size') }}</span>
                                <input
                                    type="number"
                                    name="label_font_size_px"
                                    min="11"
                                    max="18"
                                    step="1"
                                    value="{{ old('label_font_size_px', $uiAppearance['label_font_size_px'] ?? 13) }}"
                                >
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.settings_label_font_weight') }}</span>
                                <select name="label_font_weight">
                                    @foreach ($labelFontWeights as $fontWeight)
                                        <option value="{{ $fontWeight }}" @selected(old('label_font_weight', $uiAppearance['label_font_weight'] ?? '600') === $fontWeight)>
                                            {{ $fontWeight }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.settings_label_text_transform') }}</span>
                                <select name="label_text_transform">
                                    @foreach ($labelTextTransforms as $transform)
                                        <option value="{{ $transform }}" @selected(old('label_text_transform', $uiAppearance['label_text_transform'] ?? 'none') === $transform)>
                                            {{ __('messages.settings_label_text_transform_'.$transform) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <label class="lms-field" data-spacing-root>
                            <span>{{ __('messages.settings_label_letter_spacing') }}</span>
                            <div class="lms-inline-actions lms-wrap-actions lms-spacing-presets">
                                <button type="button" class="lms-btn lms-btn-soft {{ $currentLabelSpacing === 0.00 ? 'is-toggled' : '' }}" data-spacing-preset="0.00">
                                    {{ __('messages.spacing_preset_tight') }}
                                </button>
                                <button type="button" class="lms-btn lms-btn-soft {{ $currentLabelSpacing === 0.02 ? 'is-toggled' : '' }}" data-spacing-preset="0.02">
                                    {{ __('messages.spacing_preset_balanced') }}
                                </button>
                                <button type="button" class="lms-btn lms-btn-soft {{ $currentLabelSpacing === 0.05 ? 'is-toggled' : '' }}" data-spacing-preset="0.05">
                                    {{ __('messages.spacing_preset_wide') }}
                                </button>
                                <button type="button" class="lms-btn lms-btn-soft {{ $isSpacingCustom ? 'is-toggled' : '' }}" data-spacing-custom-toggle>
                                    + {{ __('messages.spacing_manual_label') }}
                                </button>
                            </div>
                            <input
                                type="number"
                                name="label_letter_spacing_em"
                                min="-0.02"
                                max="0.12"
                                step="0.01"
                                value="{{ number_format($currentLabelSpacing, 2, '.', '') }}"
                                data-spacing-input
                                class="{{ $isSpacingCustom ? '' : 'is-hidden' }}"
                            >
                            <span class="lms-muted lms-field-note">{{ __('messages.settings_label_spacing_help') }}</span>
                        </label>

                        <h5>{{ __('messages.settings_local_ui_block') }}</h5>
                        <p class="lms-muted">{{ __('messages.settings_local_ui_block_help') }}</p>
                        <div class="lms-inline-actions lms-wrap-actions" data-ui-theme-controls>
                            <button type="button" class="lms-btn lms-btn-soft" data-ui-theme-option="light">{{ __('messages.theme_light') }}</button>
                            <button type="button" class="lms-btn lms-btn-soft" data-ui-theme-option="soft">{{ __('messages.theme_soft_blue') }}</button>
                        </div>

                        <div class="lms-grid-3">
                            <label class="lms-field">
                                <span>{{ __('messages.ui_primary_color') }}</span>
                                <input type="color" value="#3b82f6" data-default="#3b82f6" data-ui-primary-color>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.ui_surface_color') }}</span>
                                <input type="color" value="#ffffff" data-default="#ffffff" data-ui-surface-color>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.ui_background_color') }}</span>
                                <input type="color" value="#f8fafc" data-default="#f8fafc" data-ui-bg-color>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.ui_success_color') }}</span>
                                <input type="color" value="#10b981" data-default="#10b981" data-ui-success-color>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.ui_danger_color') }}</span>
                                <input type="color" value="#ef4444" data-default="#ef4444" data-ui-danger-color>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.ui_text_color') }}</span>
                                <input type="color" value="#0f172a" data-default="#0f172a" data-ui-text-color>
                            </label>
                            <label class="lms-field">
                                <span>{{ __('messages.ui_border_color') }}</span>
                                <input type="color" value="#dbe5ef" data-default="#dbe5ef" data-ui-border-color>
                            </label>
                            <div class="lms-field lms-color-reset-wrap">
                                <span>{{ __('messages.ui_color_palette_reset') }}</span>
                                <button type="button" class="lms-btn lms-btn-soft" data-ui-color-reset>{{ __('messages.ui_color_palette_reset_action') }}</button>
                            </div>
                        </div>

                        <label class="lms-checkbox">
                            <input type="checkbox" data-ui-compact-toggle>
                            <span>{{ __('messages.ui_compact_mode') }}</span>
                        </label>
                    </article>
                </div>

                <div class="lms-inline-actions lms-wrap-actions">
                    <button class="lms-btn" type="submit" name="action" value="save">{{ __('messages.save_settings') }}</button>
                    <button class="lms-btn lms-btn-soft" type="submit" name="action" value="reset" formnovalidate data-reset-section>
                        {{ __('messages.reset_section_settings') }}
                    </button>
                </div>
            </form>
        @endif

        @if ($activeSection === 'pdf')
            <form method="POST" action="{{ route('settings.update') }}" class="lms-card lms-stack">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="pdf">
                @php
                    $pdfValues = [
                        'lab_name' => (int) old('report_lab_name_size_px', $layout['report_lab_name_size_px'] ?? 18),
                        'lab_meta' => (int) old('report_lab_meta_size_px', $layout['report_lab_meta_size_px'] ?? 13),
                        'title' => (int) old('report_title_size_px', $layout['report_title_size_px'] ?? 20),
                        'patient_title' => (int) old('report_patient_title_size_px', $layout['report_patient_title_size_px'] ?? 13),
                        'patient_text' => (int) old('report_patient_text_size_px', $layout['report_patient_text_size_px'] ?? 13),
                        'table_header' => (int) old('report_table_header_size_px', $layout['report_table_header_size_px'] ?? 12),
                        'table_body' => (int) old('report_table_body_size_px', $layout['report_table_body_size_px'] ?? 13),
                        'level0' => (int) old('report_level0_size_px', $layout['report_level0_size_px'] ?? 16),
                        'level1' => (int) old('report_level1_size_px', $layout['report_level1_size_px'] ?? 15),
                        'level2' => (int) old('report_level2_size_px', $layout['report_level2_size_px'] ?? 14),
                        'level3' => (int) old('report_level3_size_px', $layout['report_level3_size_px'] ?? 13),
                        'leaf' => (int) old('report_leaf_size_px', $layout['report_leaf_size_px'] ?? 13),
                    ];
                @endphp

                <div class="lms-section-title">
                    <h3>{{ __('messages.settings_nav_pdf') }}</h3>
                    <x-ui.tooltip :text="__('messages.settings_pdf_tooltip')">
                        <button type="button" class="lms-help-dot" aria-label="{{ __('messages.help') }}">!</button>
                    </x-ui.tooltip>
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

                <article class="lms-settings-panel lms-stack" data-pdf-typography-root>
                    <h4>{{ __('messages.settings_pdf_typography') }}</h4>
                    <p class="lms-muted">{{ __('messages.settings_pdf_typography_help') }}</p>
                    <p class="lms-muted">{{ __('messages.settings_pdf_typography_scope_help') }}</p>

                    <label class="lms-field">
                        <span>{{ __('messages.settings_report_font_family') }}</span>
                        <select name="report_font_family" data-report-font-select>
                            @foreach ($reportFontFamilies as $reportFont)
                                @php
                                    $reportStack = $reportFontStacks[$reportFont] ?? $reportFontStacks['medical'];
                                @endphp
                                <option
                                    value="{{ $reportFont }}"
                                    data-font-stack="{{ $reportStack }}"
                                    @selected($currentReportFont === $reportFont)
                                >
                                    {{ __('messages.settings_font_family_'.$reportFont) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="lms-font-preview" data-font-preview-report style="font-family: {{ $reportFontStacks[$currentReportFont] ?? $reportFontStacks['medical'] }};">
                        <p class="lms-font-preview-title">{{ __('messages.font_preview_title_pdf') }}</p>
                        <p class="lms-font-preview-sample">{{ __('messages.font_preview_sample') }}</p>
                    </div>

                    <div class="lms-pdf-guide-grid">
                        <div class="lms-pdf-guide-card">
                            <h5>{{ __('messages.settings_pdf_target_header') }}</h5>
                            <p>{{ __('messages.settings_pdf_target_header_help') }}</p>
                        </div>
                        <div class="lms-pdf-guide-card">
                            <h5>{{ __('messages.settings_pdf_target_patient') }}</h5>
                            <p>{{ __('messages.settings_pdf_target_patient_help') }}</p>
                        </div>
                        <div class="lms-pdf-guide-card">
                            <h5>{{ __('messages.settings_pdf_target_table') }}</h5>
                            <p>{{ __('messages.settings_pdf_target_table_help') }}</p>
                        </div>
                        <div class="lms-pdf-guide-card">
                            <h5>{{ __('messages.settings_pdf_target_hierarchy') }}</h5>
                            <p>{{ __('messages.settings_pdf_target_hierarchy_help') }}</p>
                        </div>
                    </div>

                    <div class="lms-grid-3">
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_lab_name_size') }}</span>
                            <input type="number" name="report_lab_name_size_px" min="14" max="28" step="1" value="{{ $pdfValues['lab_name'] }}" data-pdf-typo-input data-pdf-role="lab_name">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_lab_meta_size') }}</span>
                            <input type="number" name="report_lab_meta_size_px" min="10" max="20" step="1" value="{{ $pdfValues['lab_meta'] }}" data-pdf-typo-input data-pdf-role="lab_meta">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_main_title_size') }}</span>
                            <input type="number" name="report_title_size_px" min="16" max="34" step="1" value="{{ $pdfValues['title'] }}" data-pdf-typo-input data-pdf-role="title">
                        </label>
                    </div>

                    <div class="lms-grid-3">
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_patient_title_size') }}</span>
                            <input type="number" name="report_patient_title_size_px" min="11" max="20" step="1" value="{{ $pdfValues['patient_title'] }}" data-pdf-typo-input data-pdf-role="patient_title">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_patient_text_size') }}</span>
                            <input type="number" name="report_patient_text_size_px" min="10" max="18" step="1" value="{{ $pdfValues['patient_text'] }}" data-pdf-typo-input data-pdf-role="patient_text">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_table_header_size') }}</span>
                            <input type="number" name="report_table_header_size_px" min="10" max="16" step="1" value="{{ $pdfValues['table_header'] }}" data-pdf-typo-input data-pdf-role="table_header">
                        </label>
                    </div>

                    <div class="lms-grid-3">
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_table_body_size') }}</span>
                            <input type="number" name="report_table_body_size_px" min="10" max="16" step="1" value="{{ $pdfValues['table_body'] }}" data-pdf-typo-input data-pdf-role="table_body">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_level0_size') }}</span>
                            <input type="number" name="report_level0_size_px" min="12" max="20" step="1" value="{{ $pdfValues['level0'] }}" data-pdf-typo-input data-pdf-role="level0">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_level1_size') }}</span>
                            <input type="number" name="report_level1_size_px" min="12" max="18" step="1" value="{{ $pdfValues['level1'] }}" data-pdf-typo-input data-pdf-role="level1">
                        </label>
                    </div>

                    <div class="lms-grid-3">
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_level2_size') }}</span>
                            <input type="number" name="report_level2_size_px" min="11" max="17" step="1" value="{{ $pdfValues['level2'] }}" data-pdf-typo-input data-pdf-role="level2">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_level3_size') }}</span>
                            <input type="number" name="report_level3_size_px" min="10" max="16" step="1" value="{{ $pdfValues['level3'] }}" data-pdf-typo-input data-pdf-role="level3">
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.settings_report_leaf_size') }}</span>
                            <input type="number" name="report_leaf_size_px" min="10" max="16" step="1" value="{{ $pdfValues['leaf'] }}" data-pdf-typo-input data-pdf-role="leaf">
                        </label>
                    </div>

                    <p class="lms-muted" data-pdf-guide-status>{{ __('messages.settings_pdf_structure_ok') }}</p>

                    <div class="lms-pdf-preview-card" data-pdf-preview-card style="font-family: {{ $reportFontStacks[$currentReportFont] ?? $reportFontStacks['medical'] }};">
                        <div class="lms-pdf-preview-header">
                            <p data-preview-node="lab_name">{{ __('messages.preview_pdf_lab_name') }}</p>
                            <p data-preview-node="lab_meta">{{ __('messages.preview_pdf_lab_meta') }}</p>
                        </div>
                        <p class="lms-pdf-preview-main-title" data-preview-node="title">{{ __('messages.preview_pdf_main_title') }}</p>
                        <div class="lms-pdf-preview-patient">
                            <p data-preview-node="patient_title">{{ __('messages.preview_pdf_patient_title') }}</p>
                            <p data-preview-node="patient_text">{{ __('messages.preview_pdf_patient_text') }}</p>
                        </div>
                        <table class="lms-pdf-preview-table">
                            <thead>
                                <tr>
                                    <th data-preview-node="table_header">{{ __('messages.analysis') }}</th>
                                    <th data-preview-node="table_header">{{ __('messages.result') }}</th>
                                    <th data-preview-node="table_header">{{ __('messages.reference') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-preview-node="level0">HEMATOLOGIE</td>
                                    <td data-preview-node="table_body">-</td>
                                    <td data-preview-node="table_body">-</td>
                                </tr>
                                <tr>
                                    <td data-preview-node="level1">NFS</td>
                                    <td data-preview-node="table_body">-</td>
                                    <td data-preview-node="table_body">-</td>
                                </tr>
                                <tr>
                                    <td data-preview-node="level2">Globules rouges</td>
                                    <td data-preview-node="table_body">-</td>
                                    <td data-preview-node="table_body">-</td>
                                </tr>
                                <tr>
                                    <td data-preview-node="level3">Hemoglobine</td>
                                    <td data-preview-node="table_body">13.5</td>
                                    <td data-preview-node="table_body">12.5 - 16.5</td>
                                </tr>
                                <tr>
                                    <td data-preview-node="leaf">VGM</td>
                                    <td data-preview-node="table_body">89</td>
                                    <td data-preview-node="table_body">80 - 98</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <div class="lms-inline-actions lms-wrap-actions">
                    <button class="lms-btn" type="submit" name="action" value="save">{{ __('messages.save_settings') }}</button>
                    <button class="lms-btn lms-btn-soft" type="submit" name="action" value="reset" formnovalidate data-reset-section>
                        {{ __('messages.reset_section_settings') }}
                    </button>
                </div>
            </form>
        @endif

        @if ($activeSection === 'patient')
            <form method="POST" action="{{ route('settings.update') }}" class="lms-card lms-stack">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="patient">

                <div class="lms-settings-head">
                    <div class="lms-stack">
                        <div class="lms-section-title">
                            <h3>{{ __('messages.settings_nav_patient') }}</h3>
                            <x-ui.tooltip :text="__('messages.settings_patient_tooltip')">
                                <button type="button" class="lms-help-dot" aria-label="{{ __('messages.help') }}">!</button>
                            </x-ui.tooltip>
                        </div>
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
                                <th>{{ __('messages.field_key') }}</th>
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

                <div class="lms-inline-actions lms-wrap-actions">
                    <button class="lms-btn" type="submit" name="action" value="save">{{ __('messages.save_settings') }}</button>
                    <button class="lms-btn lms-btn-soft" type="submit" name="action" value="reset" formnovalidate data-reset-section>
                        {{ __('messages.reset_section_settings') }}
                    </button>
                </div>

                <x-ui.modal id="modal-add-patient-field" :title="__('messages.patient_add_field')" :open-on-load="$openAddFieldModal">
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
                </x-ui.modal>

                <x-ui.modal id="modal-edit-patient-field" :title="__('messages.patient_edit_field')">
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
                </x-ui.modal>
            </form>
        @endif
    </section>
@endsection
