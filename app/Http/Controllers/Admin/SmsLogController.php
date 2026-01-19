<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\Setting;
use App\Notifications\BookingConfirmedSms;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SmsLogController extends Controller
{
    /**
     * Afficher la liste des logs SMS
     */
    public function index(Request $request)
    {
        $query = SmsLog::with(['user', 'reservation.entreprise'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('destinataire')) {
            $query->where('destinataire', 'like', '%' . $request->destinataire . '%');
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $logs = $query->paginate(50);

        // Statistiques
        $stats = [
            'total' => SmsLog::count(),
            'envoyes' => SmsLog::where('statut', 'envoye')->count(),
            'echecs' => SmsLog::where('statut', 'echec')->count(),
            'en_attente' => SmsLog::where('statut', 'en_attente')->count(),
            'aujourd_hui' => SmsLog::whereDate('created_at', today())->count(),
        ];

        // Récupérer le mode SMS actuel (depuis Setting ou .env)
        $currentMode = Setting::get('sms_driver', env('SMS_DRIVER', 'log'));

        return view('admin.sms-logs.index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['statut', 'destinataire', 'provider', 'date_debut', 'date_fin']),
            'currentMode' => $currentMode,
        ]);
    }

    /**
     * Mettre à jour le mode SMS (log ou twilio)
     */
    public function updateMode(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|in:log,twilio',
        ]);

        // Sauvegarder dans les settings
        Setting::set('sms_driver', $validated['mode'], 'string');

        // Mettre à jour le .env également (optionnel, mais utile)
        try {
            $envFile = base_path('.env');
            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                
                // Remplacer ou ajouter SMS_DRIVER
                if (preg_match('/^SMS_DRIVER=.*/m', $envContent)) {
                    $envContent = preg_replace('/^SMS_DRIVER=.*/m', 'SMS_DRIVER=' . $validated['mode'], $envContent);
                } else {
                    $envContent .= "\nSMS_DRIVER=" . $validated['mode'];
                }
                
                file_put_contents($envFile, $envContent);
            }
        } catch (\Exception $e) {
            // Si on ne peut pas modifier le .env, ce n'est pas grave, on continue avec Setting
            \Log::warning('Impossible de modifier le .env pour SMS_DRIVER', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Mode SMS mis à jour : ' . ($validated['mode'] === 'twilio' ? 'Production (Twilio)' : 'Test (Log)'));
    }

    /**
     * Envoyer un SMS de test
     */
    public function sendTestSms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required|string|max:20',
            'message' => 'nullable|string|max:160',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $telephone = $request->telephone;
        $message = $request->message ?? 'Test SMS depuis Allotata - ' . now()->format('d/m/Y H:i');

        try {
            // Créer un "notifiable" temporaire avec le numéro de téléphone
            $testNotifiable = new class($telephone) {
                public $telephone;
                
                public function __construct($telephone) {
                    $this->telephone = $telephone;
                }
            };

            // Créer une notification de test
            $notification = new class($message) extends \Illuminate\Notifications\Notification {
                use \Illuminate\Bus\Queueable;
                
                public function __construct(public string $message) {}
                
                public function via($notifiable): array
                {
                    return ['twilio'];
                }
                
                public function toTwilio($notifiable): array
                {
                    return [
                        'message' => $this->message,
                    ];
                }
            };

            // Envoyer via le canal Twilio
            $channel = new \App\Notifications\Channels\TwilioSmsChannel();
            $channel->send($testNotifiable, $notification);

            return back()->with('success', 'SMS de test envoyé avec succès !');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de l\'envoi du SMS de test : ' . $e->getMessage()]);
        }
    }
}
