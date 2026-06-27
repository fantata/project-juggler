<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('API Token') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Generate a token to access the Project Juggler API from external tools like Claude.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.generate-token') }}" class="mt-6 space-y-6">
        @csrf
        <input type="hidden" name="type" value="api">

        @php($hasToken = auth()->user()->tokens()->exists())

        @if ($hasToken)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Token is set. Generate a new one to replace it.
            </p>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No API token generated yet.</p>
        @endif

        @if (session('api_token'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
                <p class="text-sm font-medium text-green-800 dark:text-green-300 mb-2">
                    Copy this token now — it won't be shown again:
                </p>
                <code class="block p-2 bg-white dark:bg-gray-900 rounded text-sm font-mono text-gray-900 dark:text-gray-100 break-all select-all border dark:border-gray-700">{{ session('api_token') }}</code>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>
                {{ $hasToken ? __('Regenerate Token') : __('Generate Token') }}
            </x-primary-button>
        </div>
    </form>
</section>

<section class="mt-8">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Calendar Feed') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Subscribe to your calendar from any app that supports ICS feeds.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.generate-token') }}" class="mt-6 space-y-6">
        @csrf
        <input type="hidden" name="type" value="ics">

        @if (auth()->user()->ics_feed_token)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                ICS feed token is set. Generate a new one to get a fresh URL (this will break existing subscriptions).
            </p>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No calendar feed set up yet. Generate a token to get your subscription URL.</p>
        @endif

        @if (session('ics_token'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
                <p class="text-sm font-medium text-green-800 dark:text-green-300 mb-2">
                    Your calendar feed URL (copy it now):
                </p>
                <code class="block p-2 bg-white dark:bg-gray-900 rounded text-sm font-mono text-gray-900 dark:text-gray-100 break-all select-all border dark:border-gray-700">{{ url('/ics/' . session('ics_token') . '.ics') }}</code>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>
                {{ auth()->user()->ics_feed_token ? __('Regenerate Feed URL') : __('Generate Feed URL') }}
            </x-primary-button>
        </div>
    </form>
</section>
