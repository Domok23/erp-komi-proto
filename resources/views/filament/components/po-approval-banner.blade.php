@php
    $statusConfig = match ($record->approval_status ?? 'draft') {
        'approved' => [
            'bg' => '#ecfdf5',
            'border' => '#a7f3d0',
            'text' => '#065f46',
            'darkBg' => 'rgba(6, 78, 59, 0.35)',
            'darkBorder' => 'rgba(16, 185, 129, 0.3)',
            'darkText' => '#6ee7b7',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'label' => 'Authorized & Approved',
        ],
        'rejected' => [
            'bg' => '#fff1f2',
            'border' => '#fecdd3',
            'text' => '#9f1239',
            'darkBg' => 'rgba(136, 19, 55, 0.35)',
            'darkBorder' => 'rgba(244, 63, 94, 0.3)',
            'darkText' => '#fda4af',
            'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'label' => 'Rejected',
        ],
        'pending_approval' => [
            'bg' => '#fffbeb',
            'border' => '#fde68a',
            'text' => '#92400e',
            'darkBg' => 'rgba(120, 53, 15, 0.35)',
            'darkBorder' => 'rgba(245, 158, 11, 0.3)',
            'darkText' => '#fcd34d',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'label' => 'Pending Authorization',
        ],
        default => [
            'bg' => '#f9fafb',
            'border' => '#e5e7eb',
            'text' => '#1f2937',
            'darkBg' => 'rgba(31, 41, 55, 0.6)',
            'darkBorder' => 'rgba(75, 85, 99, 0.4)',
            'darkText' => '#e5e7eb',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'label' => 'Draft',
        ],
    };

    $status = $record->approval_status ?? 'draft';
@endphp

<style>
    .po-approval-card {
        background-color: {{ $statusConfig['bg'] }};
        border: 1px solid {{ $statusConfig['border'] }};
        border-radius: 0.75rem;
        padding: 1rem;
        font-family: inherit;
    }
    .po-approval-card-title {
        color: {{ $statusConfig['text'] }};
    }
    .po-log-item {
        background-color: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(0, 0, 0, 0.06);
        color: #1f2937;
    }
    .po-log-num {
        background-color: #e5e7eb;
        color: #374151;
    }
    .po-subtext {
        color: #6b7280;
    }
    
    /* Dark mode overrides */
    .dark .po-approval-card {
        background-color: {{ $statusConfig['darkBg'] }} !important;
        border-color: {{ $statusConfig['darkBorder'] }} !important;
    }
    .dark .po-approval-card-title {
        color: {{ $statusConfig['darkText'] }} !important;
    }
    .dark .po-log-item {
        background-color: rgba(31, 41, 55, 0.7) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f3f4f6 !important;
    }
    .dark .po-log-num {
        background-color: #374151 !important;
        color: #e5e7eb !important;
    }
    .dark .po-subtext {
        color: #9ca3af !important;
    }
</style>

<div class="po-approval-card">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <svg width="24" height="24" style="width: 24px; height: 24px; min-width: 24px; max-width: 24px; flex-shrink: 0;" class="po-approval-card-title" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusConfig['icon'] }}"/>
            </svg>
            
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="po-approval-card-title" style="font-size: 0.875rem; font-weight: 700;">
                    Status: {{ $statusConfig['label'] }}
                </span>

                @if(($record->revision_number ?? 0) > 0)
                    <span style="display: inline-flex; align-items: center; font-size: 0.75rem; font-weight: 700; background-color: #dbeafe; color: #1e40af; padding: 0.125rem 0.625rem; border-radius: 9999px;">
                        Revision #{{ $record->revision_number }}
                    </span>
                @endif
            </div>
        </div>

        @if($record->parent_id)
            <div class="po-subtext" style="font-size: 0.75rem; font-weight: 500;">
                Linked from previous PO: 
                <a wire:navigate href="{{ \App\Filament\Resources\PoSupplierResource::getUrl('edit', ['record' => $record->parent_id]) }}" style="color: #3b82f6; font-weight: 600; text-decoration: underline;">
                    #{{ $record->parent->po_number ?? $record->parent_id }}
                </a>
            </div>
        @endif
    </div>

    @if($status !== 'draft')
        <div style="margin-top: 0.875rem; border-top: 1px solid rgba(128, 128, 128, 0.2); padding-top: 0.75rem;">
            <div class="po-subtext" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                Approval Flow Log
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                @forelse($record->approvals as $index => $approval)
                    <div class="po-log-item" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; font-size: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.625rem;">
                            <span class="po-log-num" style="display: flex; align-items: center; justify-content: center; width: 1.375rem; height: 1.375rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 700;">
                                {{ $index + 1 }}
                            </span>

                            @if($approval->status === 'approved')
                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 700; color: #10b981;">
                                    <svg width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Approved
                                </span>
                            @elseif($approval->status === 'rejected')
                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 700; color: #ef4444;">
                                    <svg width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Rejected
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 700; color: #f59e0b;">
                                    <svg width="16" height="16" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Awaiting Action
                                </span>
                            @endif

                            <span style="font-weight: 600;">
                                {{ $approval->approval_level === 'manager' ? 'Purchasing Manager Approval' : 'Finance Director Approval' }}
                            </span>
                        </div>

                        <div class="po-subtext" style="font-size: 0.75rem;">
                            @if($approval->status !== 'pending')
                                Signed by <strong>{{ $approval->user->name ?? 'User' }}</strong> on {{ $approval->actioned_at ? $approval->actioned_at->format('d M Y H:i') : '-' }}
                            @else
                                Min. Amount: IDR {{ number_format($approval->approval_level === 'manager' ? 0 : \App\Models\PoSupplier::DIRECTOR_APPROVAL_THRESHOLD) }}
                            @endif
                        </div>
                    </div>

                    @if($approval->status === 'rejected' && $approval->rejection_reason)
                        <div style="margin-left: 1.5rem; font-size: 0.75rem; color: #ef4444; background-color: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; padding: 0.5rem 0.75rem; border-radius: 0 0.375rem 0.375rem 0;">
                            <strong>Reason for Rejection:</strong> {{ $approval->rejection_reason }}
                        </div>
                    @endif
                @empty
                    <div class="po-subtext" style="font-size: 0.75rem;">No approval steps logged yet.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
