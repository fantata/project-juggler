<x-mail::message>
# A quick yes/no

You've been asked a question on **{{ $asker }}**:

<x-mail::panel>
{{ $issue->title }}
@if ($issue->description)

{{ $issue->description }}
@endif
</x-mail::panel>

Tap one — that's it, no login needed:

<x-mail::button :url="$yesUrl" color="success">
Yes
</x-mail::button>

<x-mail::button :url="$noUrl" color="error">
No
</x-mail::button>

Thanks,<br>
Project Juggler
</x-mail::message>
