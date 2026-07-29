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
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fde68a !important;
        padding: 4px 10px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        text-transform: none !important;
    }
    .dark .rnd-badge-pill {
        background-color: rgba(245, 158, 11, 0.2) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }
    .rnd-badge-hidden {
        visibility: hidden !important;
        opacity: 0 !important;
    }
</style>
