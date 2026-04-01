@props(['active' => false, 'href'])

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->merge([
        'class' => 'flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors ' .
            ($active
                ? 'text-terracotta-700 dark:text-terracotta-300 bg-terracotta-50 dark:bg-terracotta-900/20'
                : 'text-gray-600 dark:text-gray-400 hover:text-bark-700 dark:hover:text-cream-200 hover:bg-cream-100 dark:hover:bg-gray-700')
    ]) }}
>
    <span class="{{ $active ? 'text-terracotta-500 dark:text-terracotta-400' : 'text-gray-400 dark:text-gray-500' }}">
        {{ $icon }}
    </span>
    {{ $slot }}
</a>
