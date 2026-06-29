{{-- Floating WebRTC call — desktop only, bottom-right. Dormant until Reverb is
     running and the other person is online. --}}
@auth
    <div x-data="callWidget({{ auth()->id() }})" x-cloak
         class="hidden md:block fixed bottom-4 right-4 z-40 w-72">
        <div class="rounded-2xl bg-white dark:bg-gray-800 border border-cream-200 dark:border-gray-700 shadow-xl overflow-hidden">

            {{-- Video stage (kept in the DOM so the refs always exist) --}}
            <div class="relative bg-gray-900 aspect-video" x-show="state === 'in-call' || state === 'connecting'" x-cloak>
                <video x-ref="remoteVideo" autoplay playsinline class="w-full h-full object-cover bg-gray-900"></video>
                <video x-ref="localVideo" autoplay playsinline muted
                       class="absolute bottom-2 right-2 w-20 rounded-lg border border-white/40 object-cover bg-gray-800"></video>
            </div>

            <div class="px-4 py-3">
                {{-- Idle: a quiet call button, gated on the other person being online --}}
                <button type="button" x-show="state === 'idle'" @click="startCall()" :disabled="!otherPresent"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-moss-600 text-white px-4 py-2.5 text-sm font-semibold hover:bg-moss-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                    <span x-text="otherPresent ? ('Call ' + (otherName ?? 'them')) : 'No one online'"></span>
                </button>

                {{-- Calling out --}}
                <div x-show="state === 'calling'" x-cloak class="text-center">
                    <p class="text-sm text-bark-700 dark:text-cream-200">Calling <span x-text="otherName"></span>…</p>
                    <button type="button" @click="hangup()" class="mt-2 text-sm font-medium text-red-600 dark:text-red-400 hover:underline">Cancel</button>
                </div>

                {{-- Incoming --}}
                <div x-show="state === 'ringing'" x-cloak class="text-center">
                    <p class="text-sm font-semibold text-bark-800 dark:text-cream-200"><span x-text="otherName"></span> is calling…</p>
                    <div class="mt-2 flex gap-2">
                        <button type="button" @click="decline()" class="flex-1 rounded-lg bg-cream-100 dark:bg-gray-700 text-bark-700 dark:text-cream-200 px-3 py-1.5 text-sm font-medium hover:bg-cream-200 dark:hover:bg-gray-600">Decline</button>
                        <button type="button" @click="accept()" class="flex-1 rounded-lg bg-moss-600 text-white px-3 py-1.5 text-sm font-semibold hover:bg-moss-700">Accept</button>
                    </div>
                </div>

                {{-- In a call --}}
                <div x-show="state === 'connecting' || state === 'in-call'" x-cloak class="flex items-center justify-center gap-2">
                    <button type="button" @click="toggleMute()" :aria-pressed="muted" aria-label="Mute"
                            :class="muted ? 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' : 'bg-cream-100 dark:bg-gray-700 text-bark-700 dark:text-cream-200'"
                            class="p-2 rounded-full hover:opacity-80">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/></svg>
                    </button>
                    <button type="button" @click="toggleVideo()" :aria-pressed="!videoOn" aria-label="Toggle camera"
                            :class="videoOn ? 'bg-cream-100 dark:bg-gray-700 text-bark-700 dark:text-cream-200' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400'"
                            class="p-2 rounded-full hover:opacity-80">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    </button>
                    <button type="button" @click="hangup()" aria-label="Hang up"
                            class="p-2 rounded-full bg-red-600 text-white hover:bg-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5l18 15M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372"/></svg>
                    </button>
                </div>

                <p x-show="error" x-cloak x-text="error" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
            </div>
        </div>
    </div>
@endauth
