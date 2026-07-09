@php
    $visible = $visible ?? true;
@endphp
<span style="
    display: inline-flex !important;
    align-items: center !important;
    border-radius: 9999px !important;
    background-color: #fef3c7 !important; /* amber-100 */
    color: #92400e !important; /* amber-800 */
    padding: 4px 10px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    border: 1px solid #fde68a !important; /* amber-200 */
    line-height: 1 !important;
    text-transform: none !important;
    @if (!$visible)
        visibility: hidden !important;
        opacity: 0 !important;
    @endif
">
    from R&D
</span>
