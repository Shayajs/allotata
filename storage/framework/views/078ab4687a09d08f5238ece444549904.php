<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Statistiques des visites</h2>

    <!-- Note de mise à jour en temps réel -->
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <span class="text-sm text-blue-800 dark:text-blue-400">Données actualisées en temps réel</span>
        </div>
        <span id="last-update" class="text-xs text-blue-600 dark:text-blue-500">Dernière mise à jour : <?php echo e(now()->setTimezone('Europe/Paris')->format('H:i:s')); ?></span>
    </div>

    <!-- A. Statistiques principales (cartes) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total visites (30j)</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white" id="stat-total-visites"><?php echo e($stats['total_visites'] ?? 0); ?></p>
        </div>
        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Explorations (>7s)</p>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400" id="stat-explorations"><?php echo e($stats['visites_exploration'] ?? 0); ?></p>
        </div>
        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Taux de conversion</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400" id="stat-taux-conversion"><?php echo e($stats['taux_conversion'] ?? 0); ?>%</p>
        </div>
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Temps moyen avant réservation</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400" id="stat-temps-moyen"><?php echo e($stats['temps_moyen_avant_reservation'] ?? 0); ?>s</p>
        </div>
    </div>

    <!-- B. Graphiques -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Évolution des visites -->
        <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Évolution des visites (30 jours)</h3>
            <canvas id="evolutionVisitesChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Répartition par type de page -->
        <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Répartition par type de page</h3>
            <canvas id="repartitionPagesChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Temps moyen par page -->
        <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Temps moyen passé par page (secondes)</h3>
            <canvas id="tempsMoyenChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Taux de rebond -->
        <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Métriques clés</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400">Taux de rebond (&lt; 3s)</span>
                    <span class="text-2xl font-bold text-red-600 dark:text-red-400" id="stat-taux-rebond"><?php echo e($stats['taux_rebond'] ?? 0); ?>%</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400">Visites rapides (&lt; 3s)</span>
                    <span class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" id="stat-visites-rapides"><?php echo e($stats['visites_rapides'] ?? 0); ?></span>
                </div>
                <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <span class="text-slate-600 dark:text-slate-400">Réservations</span>
                    <span class="text-2xl font-bold text-green-600 dark:text-green-400" id="stat-reservations"><?php echo e($stats['reservations'] ?? 0); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- C. Services et produits les plus cliqués -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top 5 services -->
        <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Top 5 services les plus cliqués</h3>
            <?php if(!empty($topServices)): ?>
                <canvas id="topServicesChart" style="max-height: 250px;"></canvas>
            <?php else: ?>
                <p class="text-slate-500 dark:text-slate-400 text-center py-8">Aucun clic sur les services pour le moment</p>
            <?php endif; ?>
        </div>

        <!-- Top 5 produits -->
        <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Top 5 produits les plus cliqués</h3>
            <?php if(!empty($topProduits)): ?>
                <canvas id="topProduitsChart" style="max-height: 250px;"></canvas>
            <?php else: ?>
                <p class="text-slate-500 dark:text-slate-400 text-center py-8">Aucun clic sur les produits pour le moment</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- D. Conseils et recommandations -->
    <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-400 dark:border-yellow-600 rounded-xl mb-8">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-yellow-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-400 mb-2">Conseils et recommandations</h3>
                <p class="text-sm text-yellow-800 dark:text-yellow-500 mb-4 italic">⚠️ Les conseils sont encore en mode test</p>
                <div id="conseils-container" class="space-y-3">
                    <?php
                        $totalVisites = $stats['total_visites'] ?? 0;
                        $tauxConversion = $stats['taux_conversion'] ?? 0;
                        $tempsMoyen = $stats['temps_moyen_avant_reservation'] ?? 0;
                        $tauxRebond = $stats['taux_rebond'] ?? 0;
                        $visitesExploration = $stats['visites_exploration'] ?? 0;
                        $visitesRapides = $stats['visites_rapides'] ?? 0;
                        $reservations = $stats['reservations'] ?? 0;
                    ?>

                    
                    <?php if($totalVisites < 10): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Augmenter la visibilité :</strong> Vous avez peu de visites sur votre page publique. Pensez à partager votre lien sur vos réseaux sociaux, votre signature email, ou à faire connaître votre présence en ligne auprès de votre réseau.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($tauxRebond > 60): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Première impression :</strong> Beaucoup de visiteurs quittent en moins de 3 secondes. Vérifiez que votre photo de couverture, votre description et vos premiers éléments visuels sont accrocheurs et reflètent bien votre activité.</p>
                        </div>
                    <?php elseif($tauxRebond > 50): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Améliorer l'accroche :</strong> Plus de la moitié des visiteurs partent rapidement. Renforcez votre présentation en haut de page : une belle photo, une description claire de ce que vous proposez, et vos points forts.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($visitesRapides > 0 && ($visitesExploration / max($totalVisites, 1)) < 0.2): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Ralentir le départ :</strong> Beaucoup de visiteurs partent avant d'avoir vraiment découvert votre offre. Ajoutez du contenu engageant dès le début : témoignages, photos de réalisations, ou un message d'accueil chaleureux.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if(empty($topServices) && empty($topProduits) && $totalVisites > 5): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Stimuler l'intérêt :</strong> Les visiteurs ne cliquent pas sur vos services ou produits. Améliorez leur présentation : ajoutez des visuels attractifs, des prix clairs, des descriptions détaillées montrant la valeur ajoutée de chaque offre.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($visitesExploration > 5 && $tauxConversion < 3): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Conversion des explorateurs :</strong> Vous avez des visiteurs qui explorent (plus de 7 secondes) mais ne passent pas à l'action. Renforcez votre proposition de valeur : ajoutez des témoignages clients, des exemples de réalisations, ou des garanties qui rassurent.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($tauxConversion < 3 && $totalVisites > 10): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Optimiser les conversions :</strong> Votre taux de conversion est très faible. Améliorez la clarté de vos descriptions, ajoutez des photos de qualité, mettez en avant vos tarifs de manière transparente, et créez un sentiment d'urgence ou de confiance.</p>
                        </div>
                    <?php elseif($tauxConversion < 5 && $totalVisites > 10): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Améliorer les conversions :</strong> Votre taux de conversion peut être optimisé. Rendez vos services plus attractifs : ajoutez des détails sur ce qui est inclus, montrez des exemples concrets de votre travail, et assurez-vous que vos prix sont compétitifs.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($tempsMoyen > 600 && $reservations > 0): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Accélérer la décision :</strong> Les clients mettent beaucoup de temps avant de réserver. Aidez-les à prendre une décision plus rapide : ajoutez des créneaux avec disponibilité limitée, proposez des offres promotionnelles, ou simplifiez les informations essentielles.</p>
                        </div>
                    <?php elseif($tempsMoyen > 300 && $reservations > 0): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Faciliter la prise de décision :</strong> Les clients prennent leur temps avant de réserver. Rendez les informations clés plus visibles : horaires disponibles, tarifs, délais de réponse attendus. Ajoutez des éléments qui créent de la confiance.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if(!empty($topServices) && count($topServices) > 0 && $reservations == 0): ?>
                        <?php
                            $servicePopulaire = $topServices[0] ?? null;
                        ?>
                        <?php if($servicePopulaire): ?>
                            <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                                <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Transformer l'intérêt en action :</strong> "<?php echo e($servicePopulaire['nom']); ?>" est beaucoup consulté mais ne génère pas de réservations. Vérifiez que les informations sont complètes (description, prix, disponibilités) et que le service est bien visible sur votre page.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if(($stats['repartition_pages']['agenda'] ?? 0) > ($stats['repartition_pages']['accueil'] ?? 0) && $totalVisites > 10): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">💡 <strong>Optimiser la page d'accueil :</strong> Les visiteurs passent directement à l'agenda plutôt que d'explorer votre page principale. Enrichissez votre page d'accueil avec vos services phares, des témoignages, et vos réalisations pour mieux présenter votre activité avant qu'ils ne consultent les disponibilités.</p>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($totalVisites > 5 && $tauxConversion >= 5 && $tauxRebond < 40 && $tempsMoyen < 300): ?>
                        <div class="p-3 bg-white dark:bg-slate-800 rounded-lg">
                            <p class="text-sm text-slate-700 dark:text-slate-300">✨ <strong>Performance satisfaisante :</strong> Vos statistiques sont bonnes ! Continuez à maintenir la qualité de votre contenu et à rester réactif pour améliorer encore vos résultats. Surveillez régulièrement ces métriques pour identifier les tendances.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- E. Visiteurs connectés sans réservation -->
    <div class="p-6 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 mb-8">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Visiteurs connectés sans réservation</h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Personnes connectées qui ont visité votre enseigne mais n'ont pas réservé</p>
        
        <?php if($visiteursSansReservation->count() > 0): ?>
            <div class="space-y-4 max-h-96 overflow-y-auto">
                <?php $__currentLoopData = $visiteursSansReservation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visite): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($visite->user): ?>
                        <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                                            <?php echo e(strtoupper(substr($visite->user->name, 0, 1))); ?>

                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-slate-900 dark:text-white"><?php echo e($visite->user->name); ?></h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($visite->user->email); ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-slate-600 dark:text-slate-400">Dernière visite :</span>
                                            <p class="font-medium text-slate-900 dark:text-white"><?php echo e($visite->created_at->diffForHumans()); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 dark:text-slate-400">Temps passé :</span>
                                            <p class="font-medium text-slate-900 dark:text-white"><?php echo e($visite->duree_seconde ?? 'N/A'); ?>s</p>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 dark:text-slate-400">Clics services :</span>
                                            <p class="font-medium text-slate-900 dark:text-white"><?php echo e($visite->nb_clics_services); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-slate-600 dark:text-slate-400">Clics produits :</span>
                                            <p class="font-medium text-slate-900 dark:text-white"><?php echo e($visite->nb_clics_produits); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button 
                                        onclick="ouvrirModalContact(<?php echo e($visite->id); ?>, <?php echo e($visite->user_id); ?>, '<?php echo e($visite->user->name); ?>')"
                                        class="px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                                    >
                                        Contacter
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <p class="text-slate-500 dark:text-slate-400 text-center py-8">Aucun visiteur connecté sans réservation pour le moment</p>
        <?php endif; ?>
    </div>

    <!-- Modal de contact -->
    <div id="modal-contact" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Contacter le visiteur</h3>
                <button onclick="fermerModalContact()" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="form-contact" method="POST" action="<?php echo e(route('entreprise.statistiques.contacter', $entreprise->slug)); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="visite_id" id="contact-visite-id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Méthode de contact</label>
                    <select name="type_contact" id="contact-type" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="messagerie">Messagerie interne</option>
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Message</label>
                    <textarea name="message" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" placeholder="Votre message..." required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                        Envoyer
                    </button>
                    <button type="button" onclick="fermerModalContact()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts pour les graphiques et actualisation -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Variables encapsulées pour éviter les conflits avec d'autres onglets
        (function() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#e2e8f0' : '#1e293b';
            const gridColor = isDark ? '#334155' : '#e2e8f0';

        // Données initiales
        const statsData = <?php echo json_encode($stats, 15, 512) ?>;
        const topServices = <?php echo json_encode($topServices, 15, 512) ?>;
        const topProduits = <?php echo json_encode($topProduits, 15, 512) ?>;

        // Graphique évolution des visites
        const evolutionCtx = document.getElementById('evolutionVisitesChart');
        if (evolutionCtx) {
            new Chart(evolutionCtx, {
                type: 'line',
                data: {
                    labels: statsData.evolution_visites?.map(v => new Date(v.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })) || [],
                    datasets: [{
                        label: 'Visites',
                        data: statsData.evolution_visites?.map(v => v.count) || [],
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { labels: { color: textColor } }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } },
                        x: { ticks: { color: textColor }, grid: { color: gridColor } }
                    }
                }
            });
        }

        // Graphique répartition par page
        const repartitionCtx = document.getElementById('repartitionPagesChart');
        if (repartitionCtx) {
            new Chart(repartitionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Accueil', 'Agenda', 'Store', 'Services', 'Produits'],
                    datasets: [{
                        data: [
                            statsData.repartition_pages?.accueil || 0,
                            statsData.repartition_pages?.agenda || 0,
                            statsData.repartition_pages?.store || 0,
                            statsData.repartition_pages?.services || 0,
                            statsData.repartition_pages?.produits || 0
                        ],
                        backgroundColor: ['#22c55e', '#3b82f6', '#f97316', '#a855f7', '#ec4899']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { labels: { color: textColor }, position: 'bottom' }
                    }
                }
            });
        }

        // Graphique temps moyen
        const tempsCtx = document.getElementById('tempsMoyenChart');
        if (tempsCtx) {
            new Chart(tempsCtx, {
                type: 'bar',
                data: {
                    labels: ['Accueil', 'Agenda', 'Store', 'Services', 'Produits'],
                    datasets: [{
                        label: 'Temps moyen (s)',
                        data: [
                            Math.round(statsData.temps_moyen_par_page?.accueil || 0),
                            Math.round(statsData.temps_moyen_par_page?.agenda || 0),
                            Math.round(statsData.temps_moyen_par_page?.store || 0),
                            Math.round(statsData.temps_moyen_par_page?.services || 0),
                            Math.round(statsData.temps_moyen_par_page?.produits || 0)
                        ],
                        backgroundColor: ['#22c55e', '#3b82f6', '#f97316', '#a855f7', '#ec4899']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } },
                        x: { ticks: { color: textColor }, grid: { color: gridColor } }
                    }
                }
            });
        }

        // Graphique top services
        if (topServices.length > 0) {
            const servicesCtx = document.getElementById('topServicesChart');
            if (servicesCtx) {
                new Chart(servicesCtx, {
                    type: 'bar',
                    data: {
                        labels: topServices.map(s => s.nom.substring(0, 20) + (s.nom.length > 20 ? '...' : '')),
                        datasets: [{
                            label: 'Clics',
                            data: topServices.map(s => s.nb_clics),
                            backgroundColor: '#22c55e'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } },
                            y: { ticks: { color: textColor }, grid: { color: gridColor } }
                        }
                    }
                });
            }
        }

        // Graphique top produits
        if (topProduits.length > 0) {
            const produitsCtx = document.getElementById('topProduitsChart');
            if (produitsCtx) {
                new Chart(produitsCtx, {
                    type: 'bar',
                    data: {
                        labels: topProduits.map(p => p.nom.substring(0, 20) + (p.nom.length > 20 ? '...' : '')),
                        datasets: [{
                            label: 'Clics',
                            data: topProduits.map(p => p.nb_clics),
                            backgroundColor: '#f97316'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } },
                            y: { ticks: { color: textColor }, grid: { color: gridColor } }
                        }
                    }
                });
            }
        }

        // Actualisation en temps réel
        let updateInterval;
        function actualiserStats() {
            fetch('<?php echo e(route("entreprise.statistiques.api", $entreprise->slug)); ?>')
                .then(response => response.json())
                .then(data => {
                    // Mettre à jour les cartes
                    document.getElementById('stat-total-visites').textContent = data.stats.total_visites || 0;
                    document.getElementById('stat-explorations').textContent = data.stats.visites_exploration || 0;
                    document.getElementById('stat-taux-conversion').textContent = (data.stats.taux_conversion || 0) + '%';
                    document.getElementById('stat-temps-moyen').textContent = (data.stats.temps_moyen_avant_reservation || 0) + 's';
                    document.getElementById('stat-taux-rebond').textContent = (data.stats.taux_rebond || 0) + '%';
                    document.getElementById('stat-visites-rapides').textContent = data.stats.visites_rapides || 0;
                    document.getElementById('stat-reservations').textContent = data.stats.reservations || 0;
                    document.getElementById('last-update').textContent = 'Dernière mise à jour : ' + data.updated_at;
                })
                .catch(error => console.error('Erreur lors de l\'actualisation:', error));
        }

        // Actualiser toutes les 30 secondes
        updateInterval = setInterval(actualiserStats, 30000);
        })();
        
        // Fonctions modal (doivent être globales pour être accessibles depuis le HTML)
        function ouvrirModalContact(visiteId, userId, userName) {
            document.getElementById('contact-visite-id').value = visiteId;
            document.getElementById('modal-contact').classList.remove('hidden');
            document.getElementById('contact-type').focus();
        }

        function fermerModalContact() {
            document.getElementById('modal-contact').classList.add('hidden');
            document.getElementById('form-contact').reset();
        }

        // Fermer modal avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fermerModalContact();
            }
        });
    </script>
</div>
<?php /**PATH /var/www/html/resources/views/entreprise/dashboard/tabs/statistiques.blade.php ENDPATH**/ ?>