@if ($paginator->hasPages())
    <div class="pic-pagination">
        <span class="pic-pagination-info">
            {{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}
            de {{ number_format($paginator->total(), 0, ',', '.') }}
        </span>
        <div class="pic-pagination-btns">
            @if ($paginator->onFirstPage())
                <span class="pic-pagination-btn is-disabled">← Anterior</span>
            @else
                <button type="button" class="pic-pagination-btn" wire:click="previousPage" wire:loading.attr="disabled">← Anterior</button>
            @endif

            <span class="pic-pagination-page">Pág. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <button type="button" class="pic-pagination-btn" wire:click="nextPage" wire:loading.attr="disabled">Siguiente →</button>
            @else
                <span class="pic-pagination-btn is-disabled">Siguiente →</span>
            @endif
        </div>
    </div>
@endif
