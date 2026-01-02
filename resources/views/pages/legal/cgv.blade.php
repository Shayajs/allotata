@extends('layouts.user')

@section('title', 'Conditions Générales de Vente')

@section('content')
<div class="max-w-4xl mx-auto py-8 text-slate-900 dark:text-slate-100">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent mb-8">Conditions Générales de Vente (CGV)</h1>

    <div class="space-y-8 bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        
        <p class="text-slate-600 dark:text-slate-400 italic mb-6">
            Dernière mise à jour : 02/01/2026
        </p>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">1. Préambule</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Les présentes Conditions Générales de Vente (CGV) s'appliquent à toutes les ventes conclues sur le site Internet Allo Tata, pour les services fournis par l'auto-entreprise BrightShell. Allo Tata propose une plateforme SaaS de gestion pour les micro-entreprises, incluant des abonnements et des services payants.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">2. Prix</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Les prix de nos services sont indiqués en euros toutes taxes comprises (TTC). Allo Tata se réserve le droit de modifier ses prix à tout moment, mais le produit sera facturé sur la base du tarif en vigueur au moment de la validation de la commande.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">3. Commandes et Abonnements</h2>
            <p class="text-slate-600 dark:text-slate-400">
                L'abonnement aux services payants "Gérant" s'effectue directement sur le site. Le processus de commande comprend la sélection de l'offre, la création d'un compte ou l'identification, la vérification du détail de la commande et de son prix total, ainsi que la validation des CGV.
                La validation de la commande vaut acceptation des présentes Conditions Générales de Vente.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">4. Paiement</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Le paiement est exigible immédiatement à la commande. Le règlement des achats s'effectue par carte bancaire via notre prestataire de paiement sécurisé Stripe. Les informations de carte bancaire ne sont jamais stockées sur nos serveurs.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">5. Droit de rétractation</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Conformément aux dispositions de l'article L.121-21 du Code de la Consommation, vous disposez d'un délai de rétractation de 14 jours à compter de la conclusion du contrat (souscription de l'abonnement) pour exercer votre droit de rétractation sans avoir à justifier de motifs ni à payer de pénalité.
                Cependant, conformément à l'article L.221-28 du Code de la consommation, ce droit ne peut être exercé si l'exécution du service a commencé avant la fin du délai de rétractation avec votre accord express.
                Pour exercer ce droit, il suffit de nous contacter via le formulaire de contact ou par email.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">6. Durée et résiliation</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Les abonnements sont conclus pour une durée déterminée (mensuelle ou annuelle) et sont tacitement reconductibles. L'utilisateur peut résilier son abonnement à tout moment depuis son espace client. La résiliation prendra effet à la fin de la période de facturation en cours. Tout mois entamé est dû.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">7. Responsabilité</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Les services proposés sont conformes à la législation française en vigueur. La responsabilité de Allo Tata ne saurait être engagée en cas de non-respect de la législation du pays où le service est utilisé. 
                De plus, Allo Tata ne saurait être tenu pour responsable des dommages résultant d'une mauvaise utilisation du service acheté.
                Enfin la responsabilité de Allo Tata ne saurait être engagée pour tous les inconvénients ou dommages inhérents à l'utilisation du réseau Internet, notamment une rupture de service, une intrusion extérieure ou la présence de virus informatiques.
            </p>
        </section>

    </div>
</div>
@endsection
