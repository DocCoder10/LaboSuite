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
                            <small class="lms-muted">{{ $analysis->patient?->identifier ?? '-' }}</small>
                        </td>
                        <td>
                            <div>{{ optional($analysis->analysis_date)->format('Y-m-d') }}</div>
                            @if ($hasBeenModified)
                                <small class="lms-muted">{{ __('messages.modified_on', ['date' => optional($analysis->updated_at)->format('Y-m-d H:i')]) }}</small>
                            @endif
                        </td>
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
