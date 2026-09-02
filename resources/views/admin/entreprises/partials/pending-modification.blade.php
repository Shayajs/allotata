@php
    $pending = $entreprise->modificationEnAttente;
@endphp
@if($pending)
    @php
        $fields = $pending->fields();
        $payload = $pending->payload ?? [];
    @endphp
    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-2xl p-6 mb-8">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Modification en attente</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
            Demandée {{ $pending->created_at->diffForHumans() }}
            @if($pending->user)
                par {{ $pending->user->name }}
            @endif
            . Un administrateur doit confirmer uniquement les changements sensibles (SIREN, médias, vidéo, site web).
        </p>

        @if($fields !== [])
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500">
                            <th class="py-2 pr-4">Champ</th>
                            <th class="py-2 pr-4">En ligne</th>
                            <th class="py-2">Demande</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fields as $key => $value)
                            @if($key === 'slug')
                                @continue
                            @endif
                            <tr class="border-t border-orange-100 dark:border-orange-900/40">
                                <td class="py-2 pr-4 font-medium text-slate-800 dark:text-slate-200">{{ \App\Models\EntrepriseModification::FIELD_LABELS[$key] ?? $key }}</td>
                                <td class="py-2 pr-4 text-slate-500 dark:text-slate-400 break-all">{{ $entreprise->{$key} === true ? 'Oui' : ($entreprise->{$key} === false ? 'Non' : ($entreprise->{$key} ?? '—')) }}</td>
                                <td class="py-2 text-slate-900 dark:text-white break-all">{{ $value === true ? 'Oui' : ($value === false ? 'Non' : ($value ?? '—')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <ul class="text-sm text-slate-700 dark:text-slate-300 space-y-1 mb-4">
            @if(array_key_exists('logo', $payload))
                <li>
                    Logo :
                    @if(is_array($payload['logo'] ?? null) && !empty($payload['logo']['_delete']))
                        suppression demandée
                    @elseif(is_string($payload['logo'] ?? null))
                        <a href="{{ asset('media/'.$payload['logo']) }}" class="text-green-700 dark:text-green-400 underline" target="_blank" rel="noopener">voir le nouveau fichier</a>
                    @endif
                </li>
            @endif
            @if(array_key_exists('image_fond', $payload))
                <li>
                    Image de fond :
                    @if(is_array($payload['image_fond'] ?? null) && !empty($payload['image_fond']['_delete']))
                        suppression demandée
                    @elseif(is_string($payload['image_fond'] ?? null))
                        <a href="{{ asset('media/'.$payload['image_fond']) }}" class="text-green-700 dark:text-green-400 underline" target="_blank" rel="noopener">voir le nouveau fichier</a>
                    @endif
                </li>
            @endif
            @if(!empty($payload['photos_add']))
                <li>{{ count($payload['photos_add']) }} photo(s) à ajouter</li>
            @endif
            @if(!empty($payload['photos_delete']))
                <li>{{ count($payload['photos_delete']) }} photo(s) à retirer</li>
            @endif
        </ul>

        <div class="flex flex-wrap gap-2">
            <form action="{{ route('admin.entreprises.modifications.approve', $pending) }}" method="POST">
                @csrf
                <button type="submit" class="ui-btn-simple px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg">
                    Confirmer et publier
                </button>
            </form>
            <form action="{{ route('admin.entreprises.modifications.reject', $pending) }}" method="POST" class="flex flex-wrap gap-2">
                @csrf
                <input type="text" name="motif_refus" maxlength="1000" placeholder="Motif (optionnel)" class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-sm">
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-lg">
                    Refuser
                </button>
            </form>
        </div>
    </div>
@endif
