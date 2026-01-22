@extends('brightshell.layout')

@section('title', 'Agenda')

@php
    $carbonDate = \Carbon\Carbon::parse($date);
    $prev = $carbonDate->copy()->subDay()->format('Y-m-d');
    $next = $carbonDate->copy()->addDay()->format('Y-m-d');
    $isToday = $date === now()->format('Y-m-d');
    $typeColors = [
        'rdv' => '#5bbce4',
        'deadline' => '#ef4444',
        'rappel' => '#f59e0b',
        'autre' => '#8b5cf6',
    ];
    $HOUR_START = 6;
    $HOUR_END = 24;
    $ROW_HEIGHT = 52;
    $TOTAL_MINUTES = ($HOUR_END - $HOUR_START) * 60;
    $GRID_HEIGHT = ($HOUR_END - $HOUR_START) * $ROW_HEIGHT;
    $eventsWithTime = collect($events)->filter(fn($e) => !empty($e->heure));
    $eventsAllDay = collect($events)->filter(fn($e) => empty($e->heure));
@endphp

@section('actions')
<div class="flex items-center gap-2">
    <a href="{{ route('brightshell.agenda', ['date' => $prev]) }}" class="btn btn-secondary btn-sm" title="Jour précédent">←</a>
    <a href="{{ route('brightshell.agenda', ['date' => $next]) }}" class="btn btn-secondary btn-sm" title="Jour suivant">→</a>
    @if(!$isToday)
    <a href="{{ route('brightshell.agenda', ['date' => now()->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm">Aujourd'hui</a>
    @endif
</div>
@endsection

@section('content')
<div class="agenda-page">
    <!-- Nav date -->
    <div class="card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="card-title" style="margin: 0;">
                {{ $carbonDate->translatedFormat('l d F Y') }}
                @if($isToday)
                <span class="badge badge-info" style="margin-left: 0.5rem;">Aujourd'hui</span>
                @endif
            </h2>
            <form action="{{ route('brightshell.agenda') }}" method="GET" class="flex gap-2">
                <input type="date" name="date" class="form-input" value="{{ $date }}" style="width: auto;" onchange="this.form.submit()">
                <button type="submit" class="btn btn-secondary btn-sm">Voir</button>
            </form>
        </div>
    </div>

    <div class="grid grid-2" style="align-items: start;">
        <!-- Formulaire ajout -->
        <div class="card">
            <h3 class="card-title mb-4">Nouvel événement</h3>
            <form action="{{ route('brightshell.agenda.store') }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <div class="form-group">
                    <label class="form-label">Titre *</label>
                    <input type="text" name="titre" class="form-input" required>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Heure début</label>
                        <input type="time" name="heure" class="form-input" id="agenda-heure">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Heure fin</label>
                        <input type="time" name="heure_fin" class="form-input" id="agenda-heure-fin">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-input">
                        <option value="rdv">Rendez-vous</option>
                        <option value="deadline">Deadline</option>
                        <option value="rappel">Rappel</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>

        <!-- Vue jour type Google Agenda -->
        <div class="card" style="overflow: hidden;">
            <h3 class="card-title mb-4">Emploi du temps</h3>

            @if($eventsAllDay->isNotEmpty())
            <div class="agenda-allday">
                <div class="agenda-allday-label">Sans horaire</div>
                <div class="agenda-allday-list">
                    @foreach($eventsAllDay as $e)
                    @php $c = $typeColors[$e->type] ?? '#8b9dc3'; @endphp
                    <div class="agenda-allday-item" style="border-left-color: {{ $c }};">
                        <span class="agenda-allday-titre">{{ $e->titre }}</span>
                        <form action="{{ route('brightshell.agenda.delete', $e->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cet événement ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">×</button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="agenda-day-wrap">
                <div class="agenda-scroll">
                <div class="agenda-hours" style="height: {{ $GRID_HEIGHT }}px;">
                    @for($h = $HOUR_START; $h < $HOUR_END; $h++)
                    <div class="agenda-hour" style="height: {{ $ROW_HEIGHT }}px;">{{ sprintf('%02d', $h) }}:00</div>
                    @endfor
                </div>
                <div class="agenda-grid" style="height: {{ $GRID_HEIGHT }}px;">
                    @for($h = $HOUR_START; $h < $HOUR_END; $h++)
                    <div class="agenda-grid-line" style="height: {{ $ROW_HEIGHT }}px;"></div>
                    @endfor
                    @foreach($eventsWithTime as $e)
                    @php
                        $startM = \Carbon\Carbon::parse('2000-01-01 ' . $e->heure)->diffInMinutes(\Carbon\Carbon::parse('2000-01-01 00:00'));
                        $endM = $e->heure_fin
                            ? \Carbon\Carbon::parse('2000-01-01 ' . $e->heure_fin)->diffInMinutes(\Carbon\Carbon::parse('2000-01-01 00:00'))
                            : $startM + 60;
                        if ($endM <= $startM) { $endM = $startM + 60; }
                        $offsetM = $HOUR_START * 60;
                        $top = (max(0, $startM - $offsetM) / 60) * $ROW_HEIGHT;
                        $durationM = min($endM - $startM, ($HOUR_END - $HOUR_START) * 60);
                        $height = max(24, ($durationM / 60) * $ROW_HEIGHT);
                        $c = $typeColors[$e->type] ?? '#8b9dc3';
                    @endphp
                    <div class="agenda-event" style="top: {{ $top }}px; height: {{ $height }}px; border-left-color: {{ $c }};" title="{{ $e->titre }}{{ $e->description ? ' — ' . Str::limit($e->description, 60) : '' }}">
                        <span class="agenda-event-time">{{ substr($e->heure, 0, 5) }}@if($e->heure_fin)–{{ substr($e->heure_fin, 0, 5) }}@endif</span>
                        <span class="agenda-event-titre">{{ $e->titre }}</span>
                        <form action="{{ route('brightshell.agenda.delete', $e->id) }}" method="POST" class="agenda-event-delete" onsubmit="return confirm('Supprimer cet événement ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">×</button>
                        </form>
                    </div>
                    @endforeach
                </div>
                </div>
            </div>
            @if($eventsWithTime->isEmpty() && $eventsAllDay->isEmpty())
            <p class="text-muted text-center" style="padding: 2rem;">Aucun événement ce jour-là.</p>
            @endif
        </div>
    </div>
</div>

<style>
.agenda-page .grid.grid-2 { align-items: start; }
.agenda-day-wrap { margin-top: 0.5rem; }
.agenda-scroll { display: flex; overflow-y: auto; overflow-x: auto; border: 1px solid var(--bs-border); border-radius: 8px; max-height: 70vh; }
.agenda-hours { width: 56px; flex-shrink: 0; padding-top: 2px; border-right: 1px solid var(--bs-border); }
.agenda-hour { font-size: 0.7rem; color: var(--bs-text-muted); text-align: right; padding-right: 0.75rem; }
.agenda-grid { flex: 1; position: relative; min-width: 280px; }
.agenda-grid-line { border-bottom: 1px solid var(--bs-border); }
.agenda-event { display: block; position: absolute; left: 4px; right: 4px; margin-top: 2px; padding: 6px 8px; background: var(--bs-bg-hover); border-radius: 6px; border-left: 4px solid; color: inherit; text-decoration: none; overflow: hidden; transition: background 0.2s; }
.agenda-event:hover { background: rgba(91, 188, 228, 0.15); }
.agenda-event-time { font-size: 0.7rem; color: var(--bs-text-muted); display: block; margin-bottom: 2px; }
.agenda-event-titre { font-size: 0.85rem; font-weight: 600; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.agenda-event-delete { position: absolute; top: 4px; right: 4px; }
.agenda-event-delete .btn { padding: 0.2rem 0.4rem; font-size: 0.75rem; }
.agenda-allday { display: flex; gap: 0.5rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--bs-border); }
.agenda-allday-label { width: 56px; flex-shrink: 0; font-size: 0.7rem; color: var(--bs-text-muted); }
.agenda-allday-list { flex: 1; display: flex; flex-wrap: wrap; gap: 0.5rem; }
.agenda-allday-item { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: var(--bs-bg-dark); border-radius: 6px; border-left: 4px solid; }
.agenda-allday-titre { font-size: 0.875rem; font-weight: 500; }
.agenda-scroll { scrollbar-width: thin; scrollbar-color: rgba(91, 188, 228, 0.4) var(--bs-bg-dark); }
.agenda-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
.agenda-scroll::-webkit-scrollbar-track { background: var(--bs-bg-dark); border-radius: 4px; }
.agenda-scroll::-webkit-scrollbar-thumb { background: rgba(91, 188, 228, 0.35); border-radius: 4px; }
.agenda-scroll::-webkit-scrollbar-thumb:hover { background: rgba(91, 188, 228, 0.55); }
</style>
@endsection
