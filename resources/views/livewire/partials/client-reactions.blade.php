{{-- Reaction row for a card. Reused in the list and the detail sheet.
     Expects: $card (with reactions loaded), $reactionSet, $guestKey. --}}
@php($byKey = $card->reactions->groupBy('emoji'))

<div class="flex flex-wrap items-center gap-1.5">
    @foreach ($reactionSet as $key => $label)
        @php($group = $byKey[$key] ?? collect())
        @php($count = $group->count())
        @php($mine = $guestKey !== '' && $group->contains('guest_key', $guestKey))

        <button type="button" wire:click.stop="react({{ $card->id }}, '{{ $key }}')"
                aria-pressed="{{ $mine ? 'true' : 'false' }}"
                aria-label="{{ $label }}{{ $count ? ' — '.$count : '' }}"
                title="{{ $label }}"
                class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-sm transition
                       {{ $mine
                          ? 'border-terracotta-300 bg-terracotta-50 text-terracotta-700 dark:border-terracotta-700 dark:bg-terracotta-900/30 dark:text-terracotta-300'
                          : 'border-cream-200 text-gray-500 hover:border-terracotta-200 hover:text-terracotta-600 dark:border-gray-700 dark:text-gray-400 dark:hover:text-terracotta-300' }}">
            <x-reaction-icon :name="$key" />
            @if ($count)
                <span class="tabular-nums">{{ $count }}</span>
            @endif
        </button>
    @endforeach
</div>
