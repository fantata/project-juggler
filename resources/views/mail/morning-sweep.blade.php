<x-mail::message>
# Morning, {{ str($user->name)->before(' ') }} 👋

Here's what's looking at you on the boards.

@if ($awaitingYou->isNotEmpty())
## Waiting on your yes/no
@foreach ($awaitingYou as $question)
- **{{ $question->title }}** — {{ $question->project->name }}
@endforeach
@endif

@if ($assigned->isNotEmpty())
## On your plate
@foreach ($assigned as $card)
- {{ $card->title }} — {{ $card->project->name }}@if ($card->urgency->value === 'high') · _high_@endif
@endforeach
@endif

<x-mail::button :url="route('tasks')">
Open Juggler
</x-mail::button>

Have a good one,<br>
Project Juggler
</x-mail::message>
