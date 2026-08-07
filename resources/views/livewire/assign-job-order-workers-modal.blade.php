<div x-data @workers-assigned.window="$dispatch('close-modal'); const closeBtn = $el.closest('.fi-modal')?.querySelector('button[x-on\\:click*=\'close\']'); if (closeBtn) closeBtn.click();">
    {{ $this->table }}
</div>
