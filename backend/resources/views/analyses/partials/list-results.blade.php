@if ($analyses->isEmpty())
    <div class="lms-card">{{ __('messages.empty_analyses') }}</div>
@else
    <div class="lms-table-wrap lms-table-wrap-list">
        <table class="lms-table lms-table-list">
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
                    @php
                        $hasBeenModified = $analysis->updated_at && $analysis->created_at
                            && $analysis->updated_at->ne($analysis->created_at);
                    @endphp
                    <tr>
                        <td>{{ $analysis->analysis_number }}</td>
                        <td>
                            <div>{{ $analysis->patient?->full_name ?? '-' }}</div>
                            <small class="lms-muted">{{ $analysis->patient?->display_identifier ?? '-' }}</small>
                        </td>
                        <td>
                            <div>{{ optional($analysis->analysis_date)->format('Y-m-d') }}</div>
                            @if ($hasBeenModified)
                                <small class="lms-muted">{{ __('messages.modified_on', ['date' => optional($analysis->updated_at)->format('Y-m-d H:i')]) }}</small>
                            @endif
                        </td>
                        <td class="lms-actions-cell">
                            <div class="lms-table-actions">
                                <a class="lms-btn lms-action-pill" href="{{ route('analyses.show', ['analysis' => $analysis] + $listQuery) }}">
                                    <x-ui.icon name="eye" class="h-4 w-4" />
                                    <span>{{ __('messages.view') }}</span>
                                </a>
                                <a class="lms-btn lms-action-pill" href="{{ route('analyses.print', ['analysis' => $analysis] + $listQuery) }}" target="_blank" rel="noopener">
                                    <x-ui.icon name="print" class="h-4 w-4" />
                                    <span>{{ __('messages.print') }}</span>
                                </a>
                                <a class="lms-btn lms-action-pill" href="{{ route('analyses.edit', ['analysis' => $analysis] + $listQuery) }}">
                                    <x-ui.icon name="pencil" class="h-4 w-4" />
                                    <span>{{ __('messages.edit') }}</span>
                                </a>
                                <form method="POST" action="{{ route('analyses.destroy', ['analysis' => $analysis] + $listQuery) }}" data-analysis-delete-form data-delete-message="{{ __('messages.confirm_delete_analysis_number', ['number' => $analysis->analysis_number]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="lms-btn lms-action-pill is-danger"
                                        type="submit"
                                        data-tooltip="{{ __('messages.delete') }}"
                                        aria-label="{{ __('messages.delete') }} {{ $analysis->analysis_number }}"
                                    >
                                        <x-ui.icon name="trash" class="h-4 w-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="lms-list-footer">
        <p class="lms-muted">{{ __('messages.total_results', ['count' => $analyses->total()]) }}</p>
        {{ $analyses->links() }}
    </div>
@endif
