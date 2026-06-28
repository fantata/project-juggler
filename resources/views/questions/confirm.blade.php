<x-guest-layout>
    <div class="text-center">
        <p class="text-gray-600 dark:text-gray-400">You're answering</p>
        <p class="mt-1 text-lg font-semibold text-bark-800 dark:text-cream-200">&ldquo;{{ $issue->title }}&rdquo;</p>

        <form method="POST" action="{{ $commitUrl }}" class="mt-6">
            @csrf
            <button type="submit"
                @class([
                    'w-full inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900',
                    'bg-moss-500 hover:bg-moss-600 focus:ring-moss-400' => $answer === 'yes',
                    'bg-terracotta-500 hover:bg-terracotta-600 focus:ring-terracotta-400' => $answer === 'no',
                ])>
                {{ $answer === 'yes' ? '👍' : '✋' }} Confirm: {{ ucfirst($answer) }}
            </button>
        </form>

        <p class="mt-4 text-sm text-gray-400 dark:text-gray-500">One tap and they'll see it in Juggler.</p>
    </div>
</x-guest-layout>
