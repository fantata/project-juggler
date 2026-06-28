<x-guest-layout>
    <div class="text-center">
        <div class="text-4xl mb-3">{{ $answer === 'yes' ? '👍' : '✋' }}</div>
        <h1 class="text-xl font-semibold text-bark-800 dark:text-cream-200">Thanks — that's logged.</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            You answered
            <span class="font-semibold {{ $answer === 'yes' ? 'text-moss-600 dark:text-moss-400' : 'text-terracotta-600 dark:text-terracotta-400' }}">{{ ucfirst($answer) }}</span>
            to:
        </p>
        <p class="mt-1 text-bark-700 dark:text-cream-100">&ldquo;{{ $issue->title }}&rdquo;</p>
        <p class="mt-5 text-sm text-gray-500 dark:text-gray-400">You can close this tab — they'll see it in Juggler.</p>
    </div>
</x-guest-layout>
