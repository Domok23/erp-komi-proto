<div 
    class="material-picker-modal-wrap"
    x-data="{
        focusSearch() {
            setTimeout(() => {
                const searchInput = this.$el.querySelector('.fi-ta-search-field input, input[type=search], input[placeholder*=Search]');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 100);
        }
    }"
    x-init="focusSearch()"
    x-on:open-modal.window="focusSearch()"
>
    <style>
        .material-picker-modal-wrap .fi-ta-filters-header {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters-heading {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters-actions-ctn {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters label,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-wrp-label,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-label-col,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-label-ctn {
            display: none !important;
        }
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field-wrp,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-field,
        .material-picker-modal-wrap .fi-ta-filters .fi-fo-select-wrp {
            gap: 0 !important;
            row-gap: 0 !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        .material-picker-modal-wrap .fi-ta-filters {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
    </style>

    {{ $this->table }}
</div>
