@php
    $statusConfig = match ($record->approval_status ?? 'draft') {
        'approved' => [
            'iconColor' => 'color: #059669;',
            'darkIconColor' => 'color: #34d399 !important;',
            'badgeClass' => 'po-badge-approved',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'label' => 'Authorized & Approved',
        ],
        'rejected' => [
            'iconColor' => 'color: #dc2626;',
            'darkIconColor' => 'color: #f87171 !important;',
            'badgeClass' => 'po-badge-rejected',
            'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'label' => 'Rejected',
        ],
        'pending_approval' => [
            'iconColor' => 'color: #d97706;',
            'darkIconColor' => 'color: #fbbf24 !important;',
            'badgeClass' => 'po-badge-pending',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'label' => 'Pending Authorization',
        ],
        default => [
            'iconColor' => 'color: #64748b;',
            'darkIconColor' => 'color: #94a3b8 !important;',
            'badgeClass' => 'po-badge-draft',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'label' => 'Draft',
        ],
    };

    $status = $record->approval_status ?? 'draft';
@endphp

<style>
    .po-approval-card {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1rem;
        font-family: inherit;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .dark .po-approval-card {
        background-color: #18181b !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        box-shadow: none !important;
    }
    .po-approval-icon {
        {{ $statusConfig['iconColor'] }}
    }
    .dark .po-approval-icon {
        {{ $statusConfig['darkIconColor'] }}
    }
    .po-approval-heading {
        color: #111827;
    }
    .dark .po-approval-heading {
        color: #f9fafb !important;
    }
    .po-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.025em;
        border-width: 1px;
        border-style: solid;
        line-height: 1.4;
    }
    .po-badge-approved {
        background-color: rgba(16, 185, 129, 0.1);
        color: #047857;
        border-color: rgba(16, 185, 129, 0.25);
    }
    .dark .po-badge-approved {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #34d399 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
    .po-badge-rejected {
        background-color: rgba(239, 68, 68, 0.1);
        color: #b91c1c;
        border-color: rgba(239, 68, 68, 0.25);
    }
    .dark .po-badge-rejected {
        background-color: rgba(239, 68, 68, 0.15) !important;
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.3) !important;
    }
    .po-badge-pending {
        background-color: rgba(245, 158, 11, 0.1);
        color: #b45309;
        border-color: rgba(245, 158, 11, 0.25);
    }
    .dark .po-badge-pending {
        background-color: rgba(245, 158, 11, 0.15) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    .po-badge-draft {
        background-color: rgba(100, 116, 139, 0.1);
        color: #475569;
        border-color: rgba(100, 116, 139, 0.25);
    }
    .dark .po-badge-draft {
        background-color: rgba(255, 255, 255, 0.08) !important;
        color: #cbd5e1 !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }

    .po-log-item {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #1e293b;
    }
    .dark .po-log-item {
        background-color: rgba(255, 255, 255, 0.02) !important;
        border-color: rgba(255, 255, 255, 0.06) !important;
        color: #f3f4f6 !important;
    }
    .po-log-num {
        background-color: #e2e8f0;
        color: #475569;
    }
    .dark .po-log-num {
        background-color: rgba(255, 255, 255, 0.08) !important;
        color: #e2e8f0 !important;
    }
    .po-subtext {
        color: #64748b;
    }
    .dark .po-subtext {
        color: #94a3b8 !important;
    }
    .po-divider {
        border-top: 1px solid #f1f5f9;
    }
    .dark .po-divider {
        border-top-color: rgba(255, 255, 255, 0.08) !important;
    }
    .po-rejection-callout {
        margin-left: 1.5rem;
        font-size: 0.75rem;
        color: #b91c1c;
        background-color: rgba(239, 68, 68, 0.06);
        border-left: 3px solid #ef4444;
        padding: 0.5rem 0.75rem;
        border-radius: 0 0.375rem 0.375rem 0;
    }
    .dark .po-rejection-callout {
        color: #fca5a5 !important;
        background-color: rgba(239, 68, 68, 0.1) !important;
    }
</style>

<div class="po-approval-card">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <svg width="22" height="22" style="width: 22px; height: 22px; min-width: 22px; max-width: 22px; flex-shrink: 0;" class="po-approval-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusConfig['icon'] }}"/>
            </svg>
            
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="po-approval-heading" style="font-size: 0.875rem; font-weight: 700;">
                    Approval Status:
                </span>
                <span class="po-status-badge {{ $statusConfig['badgeClass'] }}">
                    {{ $statusConfig['label'] }}
                </span>

                @if(($record->revision_number ?? 0) > 0)
                    <span style="display: inline-flex; align-items: center; font-size: 0.75rem; font-weight: 600; background-color: rgba(59, 130, 246, 0.1); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.2); padding: 0.125rem 0.625rem; border-radius: 9999px;" class="dark:bg-blue-500/20 dark:text-blue-400 dark:border-blue-500/30">
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
        <div class="po-divider" style="margin-top: 0.875rem; padding-top: 0.75rem;">
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
                        <div class="po-rejection-callout">
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
