@extends('emails.layouts.main')

@section('content')
<p style="font-size: 16px; color: #3A4A46; line-height: 1.7;">Bonjour <strong>{{ $user->name ?? $user->prenom }}</strong>,</p>

<p style="font-size: 16px; color: #3A4A46; line-height: 1.7;">{{ $message }}</p>

@if(!empty($actionUrl))
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 30px 0;">
    <tr>
        <td align="center">
            <a href="{{ $actionUrl }}"
               style="background-color: #30B59B;
                      color: #ffffff !important;
                      padding: 15px 30px;
                      text-decoration: none;
                      border-radius: 12px;
                      font-weight: 600;
                      display: inline-block;
                      box-shadow: 0 4px 15px rgba(48, 181, 155, 0.2);">
                {{ $actionLabel ?? 'Voir les détails' }}
            </a>
        </td>
    </tr>
</table>
@endif

<p style="font-size: 16px; color: #3A4A46; line-height: 1.7; margin-top: 30px;">
    Cordialement,<br>
    <span style="color: #30B59B;"><strong>L’équipe LeyInvest</strong></span>
</p>
@endsection
