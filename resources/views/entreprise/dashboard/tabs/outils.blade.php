<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Outils administratifs</h2>
    <p class="text-slate-600 dark:text-slate-400 mb-8">Des outils pratiques pour gérer votre micro-entreprise au quotidien.</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Calculateur URSSAF -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center text-white text-2xl">
                    🧮
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Calculateur URSSAF</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Estimez vos cotisations</p>
                </div>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Chiffre d'affaires (période)
                    </label>
                    <input 
                        type="number" 
                        id="ca-urssaf"
                        placeholder="Ex: 1500"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        oninput="calculateUrssaf()"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Type d'activité
                    </label>
                    <select 
                        id="type-activite-urssaf"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        onchange="calculateUrssaf()"
                    >
                        <option value="service">Prestation de services (BIC) - 21.2%</option>
                        <option value="liberal">Profession libérale (BNC) - 21.1%</option>
                        <option value="vente">Vente de marchandises - 12.3%</option>
                    </select>
                </div>
                <div id="result-urssaf" class="hidden p-4 bg-white dark:bg-slate-800 rounded-xl">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">Cotisations</p>
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400" id="cotisations-urssaf">0 €</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">Net après cotisations</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400" id="net-urssaf">0 €</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rappels de déclaration -->
        <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl border border-orange-200 dark:border-orange-800 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-orange-500 flex items-center justify-center text-white text-2xl">
                    📅
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Rappels déclarations</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Ne ratez plus vos échéances</p>
                </div>
            </div>
            
            @php
                $now = now();
                $currentMonth = $now->month;
                $currentYear = $now->year;
                
                // Calcul des prochaines échéances (exemple mensuel)
                $prochainMois = $now->copy()->addMonth()->startOfMonth();
                $finDeclarationMensuelle = $prochainMois->copy()->endOfMonth();
                
                // Trimestre en cours
                $trimestre = ceil($currentMonth / 3);
                $finTrimestre = \Carbon\Carbon::create($currentYear, $trimestre * 3, 1)->endOfMonth();
            @endphp
            
            <div class="space-y-3">
                <div class="p-4 bg-white dark:bg-slate-800 rounded-xl border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">Déclaration mensuelle</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">CA de {{ $now->translatedFormat('F Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">
                                Avant le {{ $finDeclarationMensuelle->format('d/m/Y') }}
                            </p>
                            <p class="text-xs text-slate-500">{{ $finDeclarationMensuelle->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 bg-white dark:bg-slate-800 rounded-xl border-l-4 border-amber-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">Déclaration trimestrielle</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">T{{ $trimestre }} {{ $currentYear }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-amber-600 dark:text-amber-400 font-medium">
                                Avant le {{ $finTrimestre->copy()->addMonth()->endOfMonth()->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <a href="https://www.autoentrepreneur.urssaf.fr" target="_blank" class="block w-full px-4 py-3 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 text-white font-semibold rounded-lg transition-all text-center">
                    Accéder à l'URSSAF →
                </a>
            </div>
        </div>

        <!-- Générateur de devis -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-200 dark:border-green-800 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-green-500 flex items-center justify-center text-white text-2xl">
                    📝
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Générateur de devis</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Créez des devis professionnels</p>
                </div>
            </div>
            
            <form id="form-devis" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nom du client</label>
                        <input type="text" id="devis-client" placeholder="Nom du client" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date</label>
                        <input type="date" id="devis-date" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description de la prestation</label>
                    <textarea id="devis-description" rows="2" placeholder="Ex: Tressage africain complet..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Montant (€)</label>
                        <input type="number" id="devis-montant" placeholder="150" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Validité (jours)</label>
                        <input type="number" id="devis-validite" value="30" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    </div>
                </div>
                
                <button type="button" onclick="generateDevis()" class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    📄 Générer le devis (PDF)
                </button>
            </form>
        </div>

        <!-- Export comptable -->
        <div class="bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-2xl border border-purple-200 dark:border-purple-800 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500 flex items-center justify-center text-white text-2xl">
                    📊
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Export comptable</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Téléchargez vos données</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Période début</label>
                        <input type="date" id="export-debut" value="{{ now()->startOfYear()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Période fin</label>
                        <input type="date" id="export-fin" value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('factures.comptabilite', $entreprise->slug) }}" class="px-4 py-3 bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-300 font-medium rounded-lg transition text-center text-sm">
                        📈 Voir comptabilité
                    </a>
                    <button type="button" onclick="exportCSV()" class="px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-700 hover:to-purple-600 text-white font-semibold rounded-lg transition-all text-sm">
                        ⬇️ Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Résumé CA -->
        <div class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-800/50 dark:to-gray-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 lg:col-span-2">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl bg-slate-700 dark:bg-slate-600 flex items-center justify-center text-white text-2xl">
                    💰
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Résumé du chiffre d'affaires</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Pour vos déclarations URSSAF</p>
                </div>
            </div>
            
            @php
                $reservations = \App\Models\Reservation::where('entreprise_id', $entreprise->id)
                    ->where('est_paye', true)
                    ->get();
                
                $caMoisActuel = $reservations->filter(fn($r) => $r->date_reservation && $r->date_reservation->isCurrentMonth())->sum('prix');
                $caMoisPrecedent = $reservations->filter(fn($r) => $r->date_reservation && $r->date_reservation->isLastMonth())->sum('prix');
                $caTrimestreActuel = $reservations->filter(fn($r) => $r->date_reservation && $r->date_reservation->quarter === now()->quarter && $r->date_reservation->year === now()->year)->sum('prix');
                $caAnneeActuelle = $reservations->filter(fn($r) => $r->date_reservation && $r->date_reservation->year === now()->year)->sum('prix');
            @endphp
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 bg-white dark:bg-slate-800 rounded-xl text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase mb-1">Ce mois</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($caMoisActuel, 0, ',', ' ') }} €</p>
                    <p class="text-xs text-slate-500">{{ now()->translatedFormat('F') }}</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-xl text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase mb-1">Mois précédent</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($caMoisPrecedent, 0, ',', ' ') }} €</p>
                    <p class="text-xs text-slate-500">{{ now()->subMonth()->translatedFormat('F') }}</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-xl text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase mb-1">Ce trimestre</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($caTrimestreActuel, 0, ',', ' ') }} €</p>
                    <p class="text-xs text-slate-500">T{{ now()->quarter }} {{ now()->year }}</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-xl text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase mb-1">Cette année</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($caAnneeActuelle, 0, ',', ' ') }} €</p>
                    <p class="text-xs text-slate-500">{{ now()->year }}</p>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                <p class="text-sm text-yellow-800 dark:text-yellow-300">
                    <span class="font-semibold">💡 Rappel :</span> Le plafond de CA pour une micro-entreprise de services est de <strong>77 700 €/an</strong> (2024).
                    Vous êtes à <strong>{{ number_format(($caAnneeActuelle / 77700) * 100, 1) }}%</strong> du plafond.
                </p>
            </div>
        </div>

        <!-- Liens utiles -->
        <div class="bg-gradient-to-br from-cyan-50 to-teal-50 dark:from-cyan-900/20 dark:to-teal-900/20 rounded-2xl border border-cyan-200 dark:border-cyan-800 p-6 lg:col-span-2">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-cyan-500 flex items-center justify-center text-white text-2xl">
                    🔗
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Liens utiles</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Ressources pour les micro-entrepreneurs</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <a href="https://www.autoentrepreneur.urssaf.fr" target="_blank" class="p-4 bg-white dark:bg-slate-800 rounded-xl hover:shadow-md transition text-center group">
                    <div class="text-2xl mb-2">🏛️</div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-cyan-600">URSSAF</p>
                    <p class="text-xs text-slate-500">Déclarations</p>
                </a>
                <a href="https://www.impots.gouv.fr" target="_blank" class="p-4 bg-white dark:bg-slate-800 rounded-xl hover:shadow-md transition text-center group">
                    <div class="text-2xl mb-2">📋</div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-cyan-600">Impôts</p>
                    <p class="text-xs text-slate-500">Déclaration IR</p>
                </a>
                <a href="https://www.infogreffe.fr" target="_blank" class="p-4 bg-white dark:bg-slate-800 rounded-xl hover:shadow-md transition text-center group">
                    <div class="text-2xl mb-2">📑</div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-cyan-600">Infogreffe</p>
                    <p class="text-xs text-slate-500">Formalités</p>
                </a>
                <a href="https://www.service-public.fr/professionnels-entreprises" target="_blank" class="p-4 bg-white dark:bg-slate-800 rounded-xl hover:shadow-md transition text-center group">
                    <div class="text-2xl mb-2">ℹ️</div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white group-hover:text-cyan-600">Service Public</p>
                    <p class="text-xs text-slate-500">Infos entreprises</p>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Calculateur URSSAF
    function calculateUrssaf() {
        const ca = parseFloat(document.getElementById('ca-urssaf').value) || 0;
        const type = document.getElementById('type-activite-urssaf').value;
        
        let taux = 0.212; // Service BIC par défaut
        if (type === 'liberal') taux = 0.211;
        if (type === 'vente') taux = 0.123;
        
        const cotisations = ca * taux;
        const net = ca - cotisations;
        
        document.getElementById('cotisations-urssaf').textContent = cotisations.toFixed(2).replace('.', ',') + ' €';
        document.getElementById('net-urssaf').textContent = net.toFixed(2).replace('.', ',') + ' €';
        document.getElementById('result-urssaf').classList.remove('hidden');
    }

    // Générateur de devis (simple impression)
    function generateDevis() {
        const client = document.getElementById('devis-client').value || 'Client';
        const date = document.getElementById('devis-date').value;
        const description = document.getElementById('devis-description').value || 'Prestation';
        const montant = document.getElementById('devis-montant').value || '0';
        const validite = document.getElementById('devis-validite').value || '30';
        
        const entrepriseNom = "{{ $entreprise->nom }}";
        const entrepriseEmail = "{{ $entreprise->email }}";
        const entrepriseTel = "{{ $entreprise->telephone ?? '' }}";
        const entrepriseVille = "{{ $entreprise->ville ?? '' }}";
        
        const dateValidite = new Date(date);
        dateValidite.setDate(dateValidite.getDate() + parseInt(validite));
        
        const devisHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Devis - ${entrepriseNom}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
                    .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
                    .entreprise { font-size: 24px; font-weight: bold; color: #22c55e; }
                    .devis-title { text-align: center; font-size: 28px; margin: 30px 0; color: #1e293b; }
                    .info-box { background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                    .table { width: 100%; border-collapse: collapse; margin: 30px 0; }
                    .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
                    .table th { background: #f1f5f9; }
                    .total { font-size: 24px; text-align: right; font-weight: bold; color: #22c55e; }
                    .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
                    .mention { background: #fef3c7; padding: 10px; border-radius: 4px; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div>
                        <div class="entreprise">${entrepriseNom}</div>
                        <p>${entrepriseEmail}<br>${entrepriseTel}<br>${entrepriseVille}</p>
                    </div>
                    <div style="text-align: right;">
                        <p><strong>Devis N°</strong> ${Date.now().toString().slice(-6)}</p>
                        <p><strong>Date :</strong> ${new Date(date).toLocaleDateString('fr-FR')}</p>
                        <p><strong>Valide jusqu'au :</strong> ${dateValidite.toLocaleDateString('fr-FR')}</p>
                    </div>
                </div>
                
                <div class="info-box">
                    <strong>Client :</strong> ${client}
                </div>
                
                <h1 class="devis-title">DEVIS</h1>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align: right;">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>${description}</td>
                            <td style="text-align: right;">${parseFloat(montant).toFixed(2)} €</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="total">
                    Total : ${parseFloat(montant).toFixed(2)} €
                </div>
                
                <div class="mention">
                    TVA non applicable, article 293 B du CGI (micro-entreprise)
                </div>
                
                <div class="footer">
                    <p>Ce devis est valable ${validite} jours à compter de sa date d'émission.</p>
                    <p>Pour accepter ce devis, merci de le retourner signé avec la mention "Bon pour accord".</p>
                </div>
            </body>
            </html>
        `;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(devisHtml);
        printWindow.document.close();
        printWindow.print();
    }

    // Export CSV
    function exportCSV() {
        const debut = document.getElementById('export-debut').value;
        const fin = document.getElementById('export-fin').value;
        window.location.href = `{{ route('factures.comptabilite', $entreprise->slug) }}?date_debut=${debut}&date_fin=${fin}&export=csv`;
    }
</script>
