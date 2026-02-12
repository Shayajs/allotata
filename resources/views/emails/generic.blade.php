@extends('emails.layout')

@section('content')
    {!! $message !!}
    
    @if(isset($showSignature) && $showSignature !== false)
        <div class="signature">
            <p style="margin-bottom: 4px;">Cordialement,</p>
            <p class="team-name" style="color: #22c55e; font-weight: 600; margin: 0;">L'équipe Allo Tata</p>
        </div>
    @endif
@endsection
