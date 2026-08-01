@php
    $statusColors = [
        'draft' => ['bg' => 'bg-gray-50 border-gray-200 text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200', 'label' => 'Draft', 'icon' => 'heroicon-o-document-text'],
        'pending_approval' => ['bg' => 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-700/30 dark:text-amber-300', 'label' => 'Pending Approval', 'icon' => 'heroicon-o-clock'],
        'approved' => ['bg' => 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-900/20 dark:border-emerald-700/30 dark:text-emerald-300', 'label' => 'Authorized & Approved', 'icon' => 'heroicon-o-shield-check'],
        'rejected' => ['bg' => 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-900/20 dark:border-rose-700/30 dark:text-rose-300', 'label' => 'Rejected', 'icon' => 'heroicon-o-x-circle'],
    ];

    $status = $record->approval_status ?? 'draft';
    $config = $statusColors[$status] ?? $statusColors['draft'];
@endphp

<div class="po-approval-banner border rounded-xl p-4 flex flex-col gap-3 {{ $config['bg'] }}" style="font-family: system-ui, sans-serif;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg style="width: 20px; height: 20px; flex-shrink: 0;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($status === 'approved')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                @elseif($status === 'rejected')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @elseif($status === 'pending_approval')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                @endif
            </svg>
            <span style="font-size: 14px; font-weight: 700;">Status: {{ $config['label'] }}</span>
            @if($record->revision_number > 0)
                <span style="font-size: 11px; font-weight: 700; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 2px 8px; border-radius: 9999px;">
                    Revision #{{ $record->revision_number }}
                </span>
            @endif
        </div>
        
        @if($record->parent_id)
            <div style="font-size: 12px;">
                Linked from previous PO: 
                <a href="/po-suppliers/{{ $record->parent_id }}/edit" style="color: #3b82f6; font-weight: 600; text-decoration: underline;">
                    #{{ \App\Models\PoSupplier::find($record->parent_id)->po_number ?? $record->parent_id }}
                </a>
            </div>
        @endif
    </div>

    <!-- Active Approvals Stages List -->
    @if($status !== 'draft')
        <div style="border-top: 1px solid rgba(0, 0, 0, 0.05); padding-top: 12px; margin-top: 4px;">
            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; display: block; margin-bottom: 8px;" class="dark:text-gray-400">Approval Steps Logs:</span>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse($record->approvals as $approval)
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; padding: 8px 12px; background-color: rgba(255, 255, 255, 0.5); border-radius: 8px; border: 1px solid rgba(0, 0, 0, 0.03);" class="dark:bg-gray-800/40 dark:border-gray-700/50">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if($approval->status === 'approved')
                                <span style="color: #10b981; font-weight: bold; display: flex; align-items: center; gap: 4px;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Approved
                                </span>
                            @elseif($approval->status === 'rejected')
                                <span style="color: #ef4444; font-weight: bold; display: flex; align-items: center; gap: 4px;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Rejected
                                </span>
                            @else
                                <span style="color: #d97706; font-weight: bold; display: flex; align-items: center; gap: 4px;" class="animate-pulse">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Awaiting Action
                                </span>
                            @endif
                            <span style="font-weight: 600; color: #374151;" class="dark:text-gray-200">
                                {{ $approval->approval_level === 'manager' ? 'Purchasing Manager Approval' : 'Finance Director Approval' }}
                            </span>
                        </div>
                        
                        <div style="color: #6b7280; font-size: 11px;" class="dark:text-gray-400">
                            @if($approval->status !== 'pending')
                                Signed by <strong>{{ $approval->user->name ?? 'User' }}</strong> on {{ $approval->actioned_at ? $approval->actioned_at->format('d M Y H:i') : '' }}
                            @else
                                Minimum amount: IDR {{ number_format($approval->approval_level === 'manager' ? 0 : 100000000) }}
                            @endif
                        </div>
                    </div>
                    
                    @if($approval->status === 'rejected' && $approval->rejection_reason)
                        <div style="margin-left: 20px; font-size: 11px; color: #ef4444; background-color: rgba(239, 68, 68, 0.05); border-left: 3px solid #ef4444; padding: 6px 12px; border-radius: 0 6px 6px 0;">
                            <strong>Reason for Rejection:</strong> {{ $approval->rejection_reason }}
                        </div>
                    @endif
                @empty
                    <span style="font-size: 12px; color: #6b7280;" class="dark:text-gray-400">No approval steps configured.</span>
                @endforelse
            </div>
        </div>
    @endif
</div>
