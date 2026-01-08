@extends('emails.layouts.main')

@section('content')
<div style="text-align: center; font-family: sans-serif;">
    <h1 style="color: #333;">🚀 Test de Connexion Brevo</h1>
    <p>Ceci est un email généré par la console de test.</p>

    <div style="margin: 30px 0; background: #f8f9fa; border: 1px dashed #dee2e6; padding: 20px;">
        <span style="font-size: 14px; color: #6c757d; display: block; margin-bottom: 10px;">VOTRE CODE TEST</span>
        <b style="font-size: 32px; color: #2ecc71; letter-spacing: 5px;">12345</b>
    </div>

    <p style="font-size: 12px; color: #999;">
        Type de test : {{ $type }} <br>
        Généré le : {{ now()->format('d/m/Y H:i:s') }}
    </p>
</div>
@endsection
