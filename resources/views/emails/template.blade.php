@extends('emails.layout')

@section('content')
    {!! $body !!}
    
    @if(isset($signature) && $signature)
        <div class="signature">
            <p>Cordialement,</p>
            <p class="team-name">L'équipe Allo Tata</p>
        </div>
    @endif
@endsection
