@php
    $statusStyles = [
        'open' => 'bg-cream-200 text-bark-700 dark:bg-gray-700 dark:text-cream-200',
        'in_progress' => 'bg-terracotta-50 text-terracotta-700 dark:bg-terracotta-900/30 dark:text-terracotta-300',
        'done' => 'bg-moss-50 text-moss-700 dark:bg-moss-900/30 dark:text-moss-300',
    ];
    $statusLabels = ['open' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'];
@endphp

<div class="max-w-2xl mx-auto px-4 py-6 sm:py-10">
    {{-- Masthead --}}
    <header class="flex items-start justify-between gap-4 mb-6">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5">
                <svg class="w-7 h-7 shrink-0" viewBox="0 0 100 100" aria-hidden="true">
                    <path d="M20 70 Q50-10 80 70" fill="none" stroke="#C4AD74" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="20" cy="70" r="13" fill="#C2714F"/>
                    <circle cx="50" cy="18" r="13" fill="#6B8F71"/>
                    <circle cx="80" cy="70" r="13" fill="#8B6914"/>
                </svg>
                <h1 class="font-semibold text-xl sm:text-2xl text-bark-800 dark:text-cream-100 truncate">{{ $project->name }}</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">A shared board — add cards, leave notes, react.</p>
        </div>

        @if ($this->isNamed())
            <div class="shrink-0 text-right">
                <span class="inline-flex items-center gap-1.5 text-sm text-bark-700 dark:text-cream-200">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-moss-600 text-white text-xs font-semibold">
                        {{ Str::of($guestName)->trim()->substr(0, 1)->upper() }}
                    </span>
                    {{ $guestName }}
                </span>
                <button type="button" wire:click="$set('editingName', true)"
                        class="block ml-auto text-xs text-gray-400 hover:text-terracotta-600 dark:hover:text-terracotta-400">Not you?</button>
            </div>
        @endif
    </header>

    {{-- Name gate: warm, one-time. Blocks writing until we know who they are. --}}
    @if (! $this->isNamed() || $editingName)
        <form wire:submit="saveName"
              class="mb-6 rounded-2xl border border-cream-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 sm:p-5">
            <label for="guest-name" class="block text-sm font-semibold text-bark-800 dark:text-cream-200">Who's this?</label>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pop your name in so we know who said what. It stays on this device.</p>
            <div class="flex items-center gap-2 mt-3">
                <input type="text" id="guest-name" wire:model="nameInput" autofocus
                       placeholder="e.g. Sarah"
                       class="flex-1 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-sm">
                <button type="submit" class="shrink-0 rounded-xl bg-terracotta-600 text-white px-4 py-2 text-sm font-medium hover:bg-terracotta-700">Continue</button>
            </div>
            @error('nameInput') <p class="text-sm text-red-600 dark:text-red-400 mt-1.5">{{ $message }}</p> @enderror
        </form>
    @endif

    {{-- Filters + add. Sensible defaults: everything, search optional. --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @foreach (['all' => 'All', 'open' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'] as $value => $label)
            <button type="button" wire:click="$set('statusFilter', '{{ $value }}')"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition
                           {{ $statusFilter === $value
                              ? 'bg-bark-700 text-cream-100 dark:bg-cream-200 dark:text-gray-900'
                              : 'bg-white text-gray-600 border border-cream-200 hover:border-bark-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach

        <div class="relative flex-1 min-w-[8rem]">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search cards…"
                   aria-label="Search cards"
                   class="w-full rounded-full border-cream-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-sm pl-4">
        </div>
    </div>

    {{-- Add a card --}}
    @if ($this->isNamed())
        <div class="mb-5">
            @if ($showAddCard)
                <form wire:submit="addCard" class="rounded-2xl border border-cream-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <label for="new-title" class="sr-only">Card title</label>
                    <input type="text" id="new-title" wire:model="newTitle" placeholder="What's this about?"
                           class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-sm font-medium">
                    @error('newTitle') <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror

                    <label for="new-desc" class="sr-only">More detail</label>
                    <textarea id="new-desc" wire:model="newDescription" rows="3" placeholder="Add any detail (optional)…"
                              class="w-full mt-2 resize-none rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-sm"></textarea>

                    <div class="flex items-center gap-2 mt-2">
                        <button type="submit" class="rounded-xl bg-terracotta-600 text-white px-4 py-2 text-sm font-medium hover:bg-terracotta-700">Add card</button>
                        <button type="button" wire:click="$set('showAddCard', false)" class="text-sm text-gray-500 dark:text-gray-400 hover:text-bark-700 dark:hover:text-cream-200">Cancel</button>
                    </div>
                </form>
            @else
                <button type="button" wire:click="$set('showAddCard', true)"
                        class="w-full flex items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-cream-300 dark:border-gray-700 py-3.5 text-sm font-medium text-bark-600 dark:text-gray-300 hover:border-terracotta-300 hover:text-terracotta-600 dark:hover:text-terracotta-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add a card
                </button>
            @endif
        </div>
    @endif

    {{-- Card list --}}
    <div class="space-y-2.5">
        @forelse ($issues as $card)
            @php($status = $card->status->value)
            <article wire:key="card-{{ $card->id }}"
                     class="rounded-2xl border border-cream-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <button type="button" wire:click="openCard({{ $card->id }})" class="block w-full text-left">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-sm font-semibold text-bark-800 dark:text-cream-200 leading-snug">{{ $card->title }}</h3>
                        <span class="shrink-0 text-xs px-2 py-0.5 rounded-full font-medium {{ $statusStyles[$status] ?? '' }}">
                            {{ $statusLabels[$status] ?? $card->status->label() }}
                        </span>
                    </div>
                    @if ($card->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $card->description }}</p>
                    @endif

                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-400 dark:text-gray-500">
                        @if ($card->guest_name)
                            <span>by {{ $card->guest_name }}</span>
                        @endif
                        @if ($card->tasks_count > 0)
                            <span>{{ $card->completed_tasks_count }}/{{ $card->tasks_count }} done</span>
                        @endif
                        @if ($card->comments_count > 0)
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                                {{ $card->comments_count }}
                            </span>
                        @endif
                        @if ($card->attachments_count > 0)
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                                {{ $card->attachments_count }}
                            </span>
                        @endif
                    </div>
                </button>

                <div class="mt-3">
                    @include('livewire.partials.client-reactions', ['card' => $card])
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-cream-300 dark:border-gray-700 p-10 text-center">
                <p class="text-sm text-bark-600 dark:text-gray-300 font-medium">
                    {{ $search !== '' || $statusFilter !== 'all' ? 'Nothing matches that filter.' : 'Nothing here yet.' }}
                </p>
                @if ($search === '' && $statusFilter === 'all')
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add the first card to get the ball rolling.</p>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Card detail: bottom sheet on mobile, centred dialog on desktop --}}
    @if ($openCard)
        @php($detailStatus = $openCard->status->value)
        <div class="fixed inset-0 z-40 flex items-end sm:items-center justify-center sm:p-4"
             x-data x-trap.inert.noscroll="true"
             role="dialog" aria-modal="true" aria-labelledby="card-title-{{ $openCard->id }}"
             x-on:keydown.escape.window="$wire.closeCard()">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="closeCard" aria-hidden="true"></div>

            <div class="relative w-full sm:max-w-lg max-h-[92vh] overflow-y-auto bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl">
                <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 px-5 py-4 border-b border-cream-200 dark:border-gray-700 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="card-title-{{ $openCard->id }}" class="text-lg font-semibold text-bark-800 dark:text-cream-200">{{ $openCard->title }}</h2>
                        <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full font-medium {{ $statusStyles[$detailStatus] ?? '' }}">
                            {{ $statusLabels[$detailStatus] ?? $openCard->status->label() }}
                        </span>
                    </div>
                    <button type="button" wire:click="closeCard" aria-label="Close" class="-m-1 p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-5 py-4 space-y-5">
                    @if ($openCard->guest_name)
                        <p class="text-sm text-gray-500 dark:text-gray-400">Added by {{ $openCard->guest_name }}</p>
                    @endif

                    @if ($openCard->description)
                        <p class="text-sm text-bark-700 dark:text-gray-300 whitespace-pre-line">{{ $openCard->description }}</p>
                    @endif

                    {{-- Reactions --}}
                    <div>
                        @include('livewire.partials.client-reactions', ['card' => $openCard])
                    </div>

                    {{-- Checklist (read-only view of progress) --}}
                    @if ($openCard->tasks->isNotEmpty())
                        <div>
                            <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-2">Checklist</h3>
                            <ul class="space-y-1.5">
                                @foreach ($openCard->tasks as $task)
                                    <li class="flex items-start gap-2 text-sm">
                                        @if ($task->is_complete)
                                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-moss-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        @else
                                            <span class="w-4 h-4 mt-0.5 shrink-0 rounded-full border border-gray-300 dark:border-gray-600"></span>
                                        @endif
                                        <span class="{{ $task->is_complete ? 'text-gray-400 dark:text-gray-500 line-through' : 'text-bark-700 dark:text-gray-300' }}">{{ $task->description }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Files --}}
                    <div>
                        <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-2">Files</h3>

                        @if ($openCard->attachments->isNotEmpty())
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3">
                                @foreach ($openCard->attachments as $att)
                                    @php($fileUrl = route('board.file', ['token' => $token, 'attachment' => $att->id]))
                                    <div wire:key="att-{{ $att->id }}" class="group relative rounded-lg overflow-hidden border border-cream-200 dark:border-gray-700 bg-cream-50 dark:bg-gray-900/40">
                                        @if ($att->isImage())
                                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="block aspect-square">
                                                <img src="{{ $fileUrl }}" alt="{{ $att->original_name }}" class="w-full h-full object-cover">
                                            </a>
                                        @elseif (Str::startsWith((string) $att->mime_type, 'audio/'))
                                            <div class="flex flex-col justify-center aspect-square p-2">
                                                <audio controls preload="none" src="{{ $fileUrl }}" class="w-full"></audio>
                                                <span class="text-xs text-bark-700 dark:text-gray-300 truncate mt-1">{{ $att->original_name }}</span>
                                            </div>
                                        @else
                                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="flex flex-col items-center justify-center aspect-square p-2 text-center">
                                                <svg class="w-7 h-7 text-bark-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                                <span class="text-xs text-bark-700 dark:text-gray-300 truncate w-full mt-1">{{ $att->original_name }}</span>
                                            </a>
                                        @endif
                                        @if ($att->guest_key === $guestKey && $guestKey !== '')
                                            <button type="button" wire:click="deleteOwnAttachment({{ $att->id }})" wire:confirm="Remove this file?" aria-label="Remove {{ $att->original_name }}"
                                                    class="absolute top-1 right-1 rounded-full p-1 bg-white/90 dark:bg-gray-800/90 text-red-600 dark:text-red-400 opacity-0 group-hover:opacity-100 focus:opacity-100 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($this->isNamed())
                            <div class="flex flex-col sm:flex-row gap-2">
                                {{-- Drop / pick --}}
                                <div class="relative flex-1 border-2 border-dashed border-cream-300 dark:border-gray-600 rounded-xl p-4 text-center">
                                    <input type="file" multiple wire:model="files" aria-label="Add files"
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="pointer-events-none">
                                        <p class="text-sm text-bark-600 dark:text-gray-300">Drop files, or tap to add</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Images, video, audio, PDFs — up to 25 MB</p>
                                    </div>
                                    <div wire:loading wire:target="files,updatedFiles" role="status" class="absolute inset-0 flex items-center justify-center rounded-xl bg-white/80 dark:bg-gray-800/80 text-sm text-bark-600 dark:text-cream-200">
                                        Uploading…
                                    </div>
                                </div>

                                {{-- Record a voice note in the browser --}}
                                <div class="sm:w-40 shrink-0" x-data="voiceRecorder()" x-show="supported">
                                    <button type="button"
                                            x-on:click="recording ? stop() : start()"
                                            class="w-full h-full min-h-[3.5rem] flex items-center justify-center gap-2 rounded-xl border text-sm font-medium transition
                                                   border-cream-300 dark:border-gray-600 text-bark-600 dark:text-gray-300 hover:border-terracotta-300 hover:text-terracotta-600 dark:hover:text-terracotta-400"
                                            x-bind:class="recording && 'border-terracotta-400 text-terracotta-600 dark:text-terracotta-400'">
                                        <span x-show="!recording" class="inline-flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z"/></svg>
                                            Record
                                        </span>
                                        <span x-show="recording" x-cloak class="inline-flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                                            Stop <span x-text="`(${seconds}s)`" class="tabular-nums"></span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                            @error('files') <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                            @error('files.*') <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        @endif
                    </div>

                    {{-- Comments --}}
                    <div>
                        <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-2">Comments</h3>

                        @forelse ($openCard->comments as $comment)
                            <div wire:key="comment-{{ $comment->id }}" class="mb-3">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="font-medium text-bark-700 dark:text-cream-200">{{ $comment->authorName() }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                    @if ($comment->guest_key === $guestKey && $guestKey !== '')
                                        <button type="button" wire:click="deleteOwnComment({{ $comment->id }})" wire:confirm="Delete your comment?"
                                                class="ml-auto text-xs text-gray-400 hover:text-red-600 dark:hover:text-red-400">Delete</button>
                                    @endif
                                </div>
                                <p class="text-sm text-bark-700 dark:text-gray-300 whitespace-pre-line mt-0.5">{{ $comment->body }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">No comments yet.</p>
                        @endforelse

                        @if ($this->isNamed())
                            <form wire:submit="addComment" class="flex items-end gap-2">
                                <label for="comment-body" class="sr-only">Add a comment</label>
                                <textarea wire:model="commentBody" id="comment-body" rows="1" placeholder="Add a comment…"
                                          class="flex-1 resize-none rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-sm"></textarea>
                                <button type="submit" class="shrink-0 rounded-xl bg-terracotta-600 text-white px-4 py-2 text-sm font-medium hover:bg-terracotta-700">Post</button>
                            </form>
                            @error('commentBody') <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Add your name at the top to join the conversation.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    @script
    <script>
        // In-browser voice memo → uploaded through Livewire's normal file pipe.
        window.voiceRecorder = function () {
            return {
                supported: !!(navigator.mediaDevices && window.MediaRecorder),
                recording: false,
                mediaRecorder: null,
                chunks: [],
                seconds: 0,
                timer: null,
                async start() {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        const mime = MediaRecorder.isTypeSupported('audio/webm')
                            ? 'audio/webm'
                            : (MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : '');
                        this.mediaRecorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
                        this.chunks = [];
                        this.mediaRecorder.ondataavailable = (e) => { if (e.data && e.data.size) this.chunks.push(e.data); };
                        this.mediaRecorder.onstop = () => {
                            const type = this.mediaRecorder.mimeType || 'audio/webm';
                            const ext = type.includes('mp4') ? 'm4a' : 'webm';
                            const file = new File(this.chunks, 'voice-memo.' + ext, { type });
                            $wire.uploadMultiple('files', [file], () => {}, () => {}, () => {});
                            stream.getTracks().forEach((t) => t.stop());
                        };
                        this.mediaRecorder.start();
                        this.recording = true;
                        this.seconds = 0;
                        this.timer = setInterval(() => this.seconds++, 1000);
                    } catch (e) {
                        alert('We need microphone access to record a voice note.');
                    }
                },
                stop() {
                    if (this.mediaRecorder && this.recording) {
                        this.mediaRecorder.stop();
                        this.recording = false;
                        clearInterval(this.timer);
                    }
                },
            };
        };
    </script>
    @endscript
</div>
