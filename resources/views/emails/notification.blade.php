@extends('emails.layouts.main')

@section('content')
<p>Bonjour <strong>{{ $user->name }}</strong>,</p>

<p>{{ $message }}</p>

@if(!empty($actionUrl))
<div style="text-align:center">
    <a href="{{ $actionUrl }}" class="cta">
        {{ $actionLabel ?? 'Voir les détails' }}
    </a>
</div>
@endif

<p>
    Cordialement,<br>
    L’équipe <strong>LeyInvest</strong>
</p>
@endsection
