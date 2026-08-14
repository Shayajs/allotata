{{-- Carte d'un endpoint : meme forme pour la partie publique et la partie gestion. --}}
@php $entete = $entete ?? null; @endphp

<article class="rounded-xl border border-slate-800 bg-slate-900/40 overflow-hidden">
    <div class="border-b border-slate-800 px-5 py-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded bg-sky-500/10 px-2 py-1 font-mono text-xs font-semibold text-sky-400 ring-1 ring-sky-500/30">{{ $endpoint['methode'] }}</span>
            <code class="font-mono text-sm text-white break-all">{{ $endpoint['chemin'] }}</code>
        </div>
        <h3 class="mt-3 text-base font-semibold text-white">{{ $endpoint['titre'] }}</h3>
        <p class="mt-1 text-sm text-slate-400">{{ $endpoint['description'] }}</p>
    </div>

    @if (! empty($endpoint['parametres']))
        <div class="px-5 py-4">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paramètres</h4>
            <dl class="mt-3 space-y-2">
                @foreach ($endpoint['parametres'] as $nom => $role)
                    <div class="sm:flex sm:gap-4">
                        <dt class="w-32 shrink-0 font-mono text-sm text-green-400">{{ $nom }}</dt>
                        <dd class="text-sm text-slate-400">{{ $role }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    <div class="border-t border-slate-800 px-5 py-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Exemple</h4>
<pre class="mt-3 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs leading-relaxed text-slate-300 ring-1 ring-slate-800"><code>curl {{ $entete ? $entete.' ' : '' }}"{{ $baseUrl }}{{ $endpoint['exemple'] }}"</code></pre>
<pre class="mt-3 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs leading-relaxed text-slate-400 ring-1 ring-slate-800"><code>{{ $endpoint['reponse'] }}</code></pre>
    </div>
</article>
