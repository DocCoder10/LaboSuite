@php
    $showUnitColumn = (bool) ($layout['show_unit_column'] ?? false);
    $highlightAbnormal = (bool) ($layout['highlight_abnormal'] ?? true);
@endphp

<article class="lms-card lms-report">
    <header class="lms-report-head">
        <div>
            <h3>{{ $identity['name'] ?? __('messages.app_name') }}</h3>
            @if (! empty($identity['header_note']))
                <p>{{ $identity['header_note'] }}</p>
            @endif
            <p>{{ $identity['address'] ?? '' }}</p>
            <p>{{ $identity['phone'] ?? '' }} {{ ! empty($identity['email']) ? ' | '.$identity['email'] : '' }}</p>
        </div>
        <div class="text-right">
            <p><strong>{{ __('messages.analysis_number') }}:</strong> {{ $analysis->analysis_number }}</p>
            <p><strong>{{ __('messages.analysis_date') }}:</strong> {{ optional($analysis->analysis_date)->format('Y-m-d') }}</p>
            <p><strong>{{ __('messages.patient') }}:</strong> {{ $analysis->patient?->full_name }}</p>
            <p><strong>ID:</strong> {{ $analysis->patient?->identifier }}</p>
        </div>
    </header>

    @if (empty($groupedResults))
        <p>{{ __('messages.no_results') }}</p>
    @else
        @foreach ($groupedResults as $discipline)
            <section class="lms-report-discipline">
                <h4>{{ mb_strtoupper($discipline['label']) }}</h4>

                @foreach ($discipline['categories'] as $category)
                    <div class="lms-report-category">
                        <h5>{{ $category['label'] }}</h5>

                        @foreach ($category['subcategories'] as $subcategory)
                            @if (! empty($subcategory['label']))
                                <h6>{{ $subcategory['label'] }}</h6>
                            @endif

                            <table class="lms-table lms-report-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.parameter') }}</th>
                                        <th>{{ __('messages.result') }}</th>
                                        <th>{{ __('messages.reference') }}</th>
                                        @if ($showUnitColumn)
                                            <th>{{ __('messages.unit') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subcategory['rows'] as $row)
                                        <tr class="{{ $row['is_abnormal'] && $highlightAbnormal ? 'is-abnormal' : '' }}">
                                            <td>{{ $row['parameter'] }}</td>
                                            <td>{{ $row['result'] }}</td>
                                            <td>{{ $row['reference'] }}</td>
                                            @if ($showUnitColumn)
                                                <td>{{ $row['unit'] }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endforeach
                    </div>
                @endforeach
            </section>
        @endforeach
    @endif

    @if (! empty($analysis->notes))
        <section class="lms-report-notes">
            <h5>{{ __('messages.notes') }}</h5>
            <p>{{ $analysis->notes }}</p>
        </section>
    @endif

    @if (! empty($identity['footer_note']))
        <footer class="lms-report-footer">
            <p>{{ $identity['footer_note'] }}</p>
        </footer>
    @endif
</article>
