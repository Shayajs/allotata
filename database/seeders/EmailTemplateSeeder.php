<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'type' => 'welcome',
                'name' => 'Email de bienvenue',
                'subject' => 'Bienvenue sur Allo Tata, {nom_client} !',
                'body' => '<h1 style="color: #22c55e; margin-bottom: 20px;">Bienvenue sur Allo Tata, {nom_client} !</h1>
                
                <p>Nous sommes ravis de vous accueillir sur <strong>Allo Tata</strong>, la plateforme tout-en-un pour gérer votre activité professionnelle.</p>
                
                <p>Vous pouvez maintenant :</p>
                <ul>
                    <li>Créer votre première entreprise</li>
                    <li>Gérer votre agenda et vos réservations</li>
                    <li>Suivre vos finances</li>
                    <li>Communiquer avec vos clients</li>
                </ul>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_dashboard}" class="button">Accéder à mon dashboard</a>
                </div>
                
                <p>Si vous avez des questions, n\'hésitez pas à consulter notre FAQ ou à nous contacter.</p>
                
                <p>Bonne journée,<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_client', 'url_dashboard'],
                'description' => 'Email envoyé lors de l\'inscription d\'un nouvel utilisateur',
            ],
            [
                'type' => 'reservation_confirmation_client',
                'name' => 'Confirmation de réservation (Client)',
                'subject' => 'Réservation confirmée - {nom_entreprise}',
                'body' => '<h1 style="color: #22c55e; margin-bottom: 20px;">Réservation confirmée !</h1>
                
                <p>Bonjour {nom_client},</p>
                
                <p>Votre réservation a été <strong>confirmée</strong> par <strong>{nom_entreprise}</strong>.</p>
                
                <div class="info-box">
                    <h3 style="margin-top: 0;">Détails de votre réservation :</h3>
                    <p><strong>Service :</strong> {nom_service}</p>
                    <p><strong>Date et heure :</strong> {date_reservation}</p>
                    <p><strong>Durée :</strong> {duree} minutes</p>
                    <p><strong>Prix :</strong> {prix} €</p>
                    {lieu_html}
                    {membre_html}
                </div>
                
                {notes_html}
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_reservation}" class="button">Voir ma réservation</a>
                </div>
                
                <p>À bientôt !<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_client', 'nom_entreprise', 'nom_service', 'date_reservation', 'duree', 'prix', 'lieu', 'membre', 'notes', 'url_reservation'],
                'description' => 'Email de confirmation de réservation envoyé au client',
            ],
            [
                'type' => 'reservation_confirmation_gerant',
                'name' => 'Nouvelle réservation (Gérant)',
                'subject' => 'Nouvelle réservation - {nom_entreprise}',
                'body' => '<h1 style="color: #f97316; margin-bottom: 20px;">Nouvelle réservation</h1>
                
                <p>Bonjour,</p>
                
                <p>Une nouvelle réservation a été créée pour <strong>{nom_entreprise}</strong>.</p>
                
                <div class="info-box">
                    <h3 style="margin-top: 0;">Détails de la réservation :</h3>
                    <p><strong>Client :</strong> {nom_client}</p>
                    <p><strong>Service :</strong> {nom_service}</p>
                    <p><strong>Date et heure :</strong> {date_reservation}</p>
                    <p><strong>Durée :</strong> {duree} minutes</p>
                    <p><strong>Prix :</strong> {prix} €</p>
                    <p><strong>Téléphone :</strong> {telephone}</p>
                    {lieu_html}
                </div>
                
                {notes_html}
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_reservation}" class="button">Voir la réservation</a>
                </div>
                
                <p>Cordialement,<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_client', 'nom_entreprise', 'nom_service', 'date_reservation', 'duree', 'prix', 'telephone', 'lieu', 'notes', 'url_reservation'],
                'description' => 'Email envoyé au gérant lors d\'une nouvelle réservation',
            ],
            [
                'type' => 'reservation_reminder',
                'name' => 'Rappel de rendez-vous',
                'subject' => 'Rappel : Votre rendez-vous dans {heures_avant}h - {nom_entreprise}',
                'body' => '<h1 style="color: #f59e0b; margin-bottom: 20px;">Rappel de rendez-vous</h1>
                
                <p>Bonjour {nom_client},</p>
                
                <p>Ceci est un rappel : vous avez un rendez-vous prévu <strong>dans {heures_avant} heures</strong>.</p>
                
                <div class="warning-box">
                    <h3 style="margin-top: 0;">Votre rendez-vous :</h3>
                    <p><strong>Entreprise :</strong> {nom_entreprise}</p>
                    <p><strong>Service :</strong> {nom_service}</p>
                    <p><strong>Date et heure :</strong> {date_reservation}</p>
                    <p><strong>Durée :</strong> {duree} minutes</p>
                    {lieu_html}
                    {membre_html}
                </div>
                
                <p><strong>Contact :</strong> {contact_entreprise}</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_reservation}" class="button">Voir ma réservation</a>
                </div>
                
                <p>À bientôt !<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_client', 'nom_entreprise', 'nom_service', 'date_reservation', 'duree', 'lieu', 'membre', 'heures_avant', 'contact_entreprise', 'url_reservation'],
                'description' => 'Email de rappel envoyé avant un rendez-vous',
            ],
            [
                'type' => 'payment_received',
                'name' => 'Paiement reçu',
                'subject' => 'Paiement reçu - {nom_entreprise}',
                'body' => '<h1 style="color: #22c55e; margin-bottom: 20px;">Paiement reçu</h1>
                
                <p>Bonjour {nom_client},</p>
                
                <p>Nous vous confirmons la réception de votre paiement pour la réservation du <strong>{date_reservation}</strong>.</p>
                
                <div class="info-box">
                    <h3 style="margin-top: 0;">Détails du paiement :</h3>
                    <p><strong>Montant :</strong> {montant} €</p>
                    <p><strong>Date de paiement :</strong> {date_paiement}</p>
                    <p><strong>Service :</strong> {nom_service}</p>
                    <p><strong>Entreprise :</strong> {nom_entreprise}</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_reservation}" class="button">Voir ma réservation</a>
                </div>
                
                <p>Merci pour votre confiance !<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_client', 'nom_entreprise', 'nom_service', 'date_reservation', 'montant', 'date_paiement', 'url_reservation'],
                'description' => 'Email de confirmation de paiement',
            ],
            [
                'type' => 'new_message',
                'name' => 'Nouveau message',
                'subject' => 'Nouveau message - {nom_entreprise}',
                'body' => '<h1 style="color: #22c55e; margin-bottom: 20px;">Nouveau message</h1>
                
                <p>Bonjour {nom_client},</p>
                
                <p>Vous avez reçu un nouveau message de <strong>{nom_entreprise}</strong>.</p>
                
                <div class="info-box">
                    <p style="margin: 0;"><strong>Message :</strong></p>
                    <p style="margin-top: 10px; white-space: pre-wrap;">{contenu_message}</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_messagerie}" class="button">Répondre</a>
                </div>
                
                <p>Cordialement,<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_client', 'nom_entreprise', 'contenu_message', 'url_messagerie'],
                'description' => 'Email envoyé lors de la réception d\'un nouveau message',
            ],
            [
                'type' => 'reservation_cancelled_client',
                'name' => 'Réservation annulée (Client)',
                'subject' => 'Réservation annulée - {nom_entreprise}',
                'body' => '<h1 style="color: #ef4444; margin-bottom: 20px;">Réservation annulée</h1>
                
                <p>Bonjour {nom_client},</p>
                
                {message_annulation}
                
                <div class="info-box">
                    <h3 style="margin-top: 0;">Détails de la réservation annulée :</h3>
                    <p><strong>Service :</strong> {nom_service}</p>
                    <p><strong>Date et heure :</strong> {date_reservation}</p>
                    <p><strong>Prix :</strong> {prix} €</p>
                </div>
                
                {remboursement_html}
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_entreprise}" class="button">Prendre un nouveau rendez-vous</a>
                </div>
                
                <p>À bientôt !<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_client', 'nom_entreprise', 'nom_service', 'date_reservation', 'prix', 'message_annulation', 'remboursement', 'url_entreprise'],
                'description' => 'Email d\'annulation de réservation envoyé au client',
            ],
            [
                'type' => 'weekly_report',
                'name' => 'Rapport hebdomadaire',
                'subject' => 'Rapport hebdomadaire - {nom_entreprise}',
                'body' => '<h1 style="color: #22c55e; margin-bottom: 20px;">Rapport hebdomadaire</h1>
                
                <p>Bonjour {nom_gerant},</p>
                
                <p>Voici votre rapport hebdomadaire pour <strong>{nom_entreprise}</strong>.</p>
                
                <div class="info-box">
                    <h3 style="margin-top: 0;">Statistiques de la semaine :</h3>
                    <p><strong>Réservations totales :</strong> {total_reservations}</p>
                    <p><strong>Réservations confirmées :</strong> {reservations_confirmees}</p>
                    <p><strong>Réservations en attente :</strong> {reservations_en_attente}</p>
                    <p><strong>Revenu total :</strong> {revenu_total} €</p>
                    <p><strong>Revenu encaissé :</strong> {revenu_paye} €</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{url_dashboard}" class="button">Voir le dashboard</a>
                </div>
                
                <p>Bonne semaine !<br>L\'équipe Allo Tata</p>',
                'variables' => ['nom_gerant', 'nom_entreprise', 'total_reservations', 'reservations_confirmees', 'reservations_en_attente', 'revenu_total', 'revenu_paye', 'url_dashboard'],
                'description' => 'Rapport hebdomadaire envoyé aux gérants',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['type' => $template['type']],
                $template
            );
        }
    }
}
