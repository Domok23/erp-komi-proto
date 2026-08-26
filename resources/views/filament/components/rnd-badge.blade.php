@php
    $visible = $visible ?? true;
@endphp
<span class="rnd-badge-pill {{ !$visible ? 'rnd-badge-hidden' : '' }}">
    from R&amp;D
</span>
<style>
    .rnd-badge-pill {
        display: inline-flex !important;
        align-items: center !important;
        border-radius: 9999px !important;
        background-color: rgba(245, 158, 11, 0.1) !important;
        color: #b45309 !important;
        border: 1px solid rgba(245, 158, 11, 0.2) !important;
        padding: 2px 8px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
        text-transform: none !important;
    }
    .dark .rnd-badge-pill {
        background-color: rgba(245, 158, 11, 0.15) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    .rnd-badge-hidden {
        visibility: hidden !important;
        opacity: 0 !important;
    }
</style>
