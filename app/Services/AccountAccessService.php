<?php

namespace App\Services;

use App\Mail\AdminAccountAccessMail;
use App\Models\ActivityLog;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AccountAccessService
{
    public const MODE_VIEW = 'VIEW';

    public const MODE_EDIT = 'EDIT';

    public const MODE_SUPPORT = 'SUPPORT';

    public const MODE_BILLING = 'BILLING';

    public const SESSION_ADMIN_ID = 'account_access_admin_id';

    public const SESSION_MODE = 'account_access_mode';

    public const SESSION_COMPTE = 'account_access_compte';

    /** Routes utilitaires toujours autorisées en mutation (présence, broadcast, etc.) */
    private const UTILITY_ROUTES = [
        'api.presence.heartbeat',
        'messagerie.api.check-new',
        'broadcasting.auth',
        'logout',
    ];

    /** Routes exactes autorisées en mode SUPPORT */
    private const SUPPORT_ROUTES = [
        'tickets.store',
        'tickets.add-message',
        'messagerie.send',
        'messagerie.send-gerant',
    ];

    /** Préfixes de routes autorisés en mode BILLING */
    private const BILLING_ROUTE_PREFIXES = [
        'subscription.',
        'checkout.',
    ];

    /** Routes exactes autorisées en mode BILLING */
    private const BILLING_ROUTES = [
        'payment.authenticate',
        'entreprise.finances.store',
        'entreprise.finances.update',
        'entreprise.finances.destroy',
        'factures.store-groupee',
    ];

    public function __construct(
        private UserNotificationService $notifications,
    ) {}

    public function normalizeMode(?string $mode): ?string
    {
        if ($mode === null || $mode === '') {
            return null;
        }

        $mode = strtoupper(trim($mode));

        return match ($mode) {
            self::MODE_VIEW => self::MODE_VIEW,
            self::MODE_EDIT, 'ADMIN' => self::MODE_EDIT,
            self::MODE_SUPPORT => self::MODE_SUPPORT,
            self::MODE_BILLING => self::MODE_BILLING,
            default => null,
        };
    }

    public function resolveContext(Request $request): ?array
    {
        $mode = $this->normalizeMode($request->query('mode') ?? $request->input('mode'));
        $compte = (int) ($request->query('compte') ?? $request->input('compte') ?: 0);

        if ($mode && $compte > 0) {
            return ['mode' => $mode, 'compte' => $compte];
        }

        if (! $this->hasBridge()) {
            return null;
        }

        $sessionMode = $this->normalizeMode(session(self::SESSION_MODE));
        $sessionCompte = (int) session(self::SESSION_COMPTE, 0);

        if ($sessionMode && $sessionCompte > 0) {
            return ['mode' => $sessionMode, 'compte' => $sessionCompte];
        }

        return null;
    }

    /**
     * Sortie explicite (bouton Quitter) via ?exit_account_access=1
     */
    public function wantsExplicitExit(Request $request): bool
    {
        return $request->boolean('exit_account_access');
    }

    /**
     * Retour au panneau admin : on quitte automatiquement l'accès compte.
     */
    public function shouldExitOnAdminPanel(Request $request): bool
    {
        if (! $this->hasBridge()) {
            return false;
        }

        $routeName = $request->route()?->getName();

        return is_string($routeName) && str_starts_with($routeName, 'admin.');
    }

    /**
     * @deprecated Conservé pour compat tests — remplacé par wantsExplicitExit / shouldExitOnAdminPanel
     */
    public function shouldExitOnGet(Request $request): bool
    {
        return $this->wantsExplicitExit($request) || $this->shouldExitOnAdminPanel($request);
    }

    /**
     * GET sans ?mode=&compte= alors que le pont session est actif :
     * on réinjecte les params pour que l'URL reste le signal visible.
     */
    public function needsQueryInjection(Request $request): bool
    {
        if (! $request->isMethodSafe() || ! $this->hasBridge()) {
            return false;
        }

        if ($this->wantsExplicitExit($request) || $this->shouldExitOnAdminPanel($request)) {
            return false;
        }

        $mode = $this->normalizeMode($request->query('mode'));
        $compte = (int) ($request->query('compte') ?: 0);

        return ! ($mode && $compte > 0);
    }

    public function injectQueryUrl(Request $request): string
    {
        $query = array_merge($request->query(), $this->buildQuery());

        unset($query['exit_account_access']);

        return $request->url().($query !== [] ? '?'.http_build_query($query) : '');
    }

    public function exitUrl(string $routeName = 'admin.users.index', array $parameters = []): string
    {
        return route($routeName, array_merge($parameters, [
            'exit_account_access' => 1,
        ]));
    }

    public function hasBridge(): bool
    {
        return session()->has(self::SESSION_ADMIN_ID);
    }

    public function isActive(): bool
    {
        return $this->hasBridge() && $this->mode() !== null && $this->targetUserId() !== null;
    }

    public function mode(): ?string
    {
        return $this->normalizeMode(session(self::SESSION_MODE));
    }

    public function targetUserId(): ?int
    {
        $compte = (int) session(self::SESSION_COMPTE, 0);

        return $compte > 0 ? $compte : null;
    }

    public function adminId(): ?int
    {
        $adminId = session(self::SESSION_ADMIN_ID);

        return $adminId ? (int) $adminId : null;
    }

    /** True si le mode autorise au moins certaines écritures (pas VIEW). */
    public function canWrite(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return in_array($this->mode(), [
            self::MODE_EDIT,
            self::MODE_SUPPORT,
            self::MODE_BILLING,
        ], true);
    }

    /** True si la route nommée peut être mutée dans le mode courant. */
    public function canWriteRoute(?string $routeName): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $mode = $this->mode();

        if ($mode === self::MODE_VIEW) {
            return false;
        }

        if ($mode === self::MODE_EDIT) {
            return true;
        }

        if ($routeName && in_array($routeName, self::UTILITY_ROUTES, true)) {
            return true;
        }

        if ($mode === self::MODE_SUPPORT) {
            return $routeName !== null && in_array($routeName, self::SUPPORT_ROUTES, true);
        }

        if ($mode === self::MODE_BILLING) {
            if ($routeName === null) {
                return false;
            }

            if (in_array($routeName, self::BILLING_ROUTES, true)) {
                return true;
            }

            foreach (self::BILLING_ROUTE_PREFIXES as $prefix) {
                if (str_starts_with($routeName, $prefix)) {
                    return true;
                }
            }

            if (in_array($routeName, self::UTILITY_ROUTES, true)) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function modeLabel(?string $mode = null): string
    {
        return match ($mode ?? $this->mode()) {
            self::MODE_VIEW => 'Lecture seule',
            self::MODE_SUPPORT => 'Support',
            self::MODE_BILLING => 'Facturation',
            self::MODE_EDIT => 'Édition',
            default => 'Inconnu',
        };
    }

    public function enter(User $target, int $adminId, string $mode): void
    {
        $previousMode = session(self::SESSION_MODE);
        $previousCompte = (int) session(self::SESSION_COMPTE, 0);

        session([
            self::SESSION_ADMIN_ID => $adminId,
            self::SESSION_MODE => $mode,
            self::SESSION_COMPTE => $target->id,
        ]);

        URL::defaults([
            'mode' => $mode,
            'compte' => $target->id,
        ]);

        if ($previousMode !== $mode || $previousCompte !== $target->id) {
            $this->recordAccessEntry($target, $adminId, $mode);
        }
    }

    public function exit(): void
    {
        $adminId = $this->adminId();
        $mode = $this->mode();
        $compte = $this->targetUserId();

        session()->forget([
            self::SESSION_ADMIN_ID,
            self::SESSION_MODE,
            self::SESSION_COMPTE,
        ]);

        if ($compte && $mode) {
            session()->forget($this->notifiedSessionKey($mode, $compte));
        }

        URL::defaults([]);

        if ($adminId) {
            Auth::loginUsingId($adminId);
        }
    }

    public function buildQuery(?string $mode = null, ?int $compte = null): array
    {
        $mode ??= $this->mode();
        $compte ??= $this->targetUserId();

        if (! $mode || ! $compte) {
            return [];
        }

        return [
            'mode' => $mode,
            'compte' => $compte,
        ];
    }

    public function impersonationUrl(string $routeName, User $target, string $mode, array $parameters = []): string
    {
        $mode = $this->normalizeMode($mode) ?? self::MODE_VIEW;

        return route($routeName, array_merge($parameters, [
            'mode' => $mode,
            'compte' => $target->id,
        ]));
    }

    public function switchModeUrl(string $routeName, string $mode, array $parameters = []): ?string
    {
        $compte = $this->targetUserId();
        $mode = $this->normalizeMode($mode);

        if (! $compte || ! $mode) {
            return null;
        }

        return route($routeName, array_merge($parameters, [
            'mode' => $mode,
            'compte' => $compte,
        ]));
    }

    /** Change de mode en restant sur la page courante (ex. dashboard entreprise). */
    public function switchModeOnCurrentUrl(Request $request, string $mode): ?string
    {
        $mode = $this->normalizeMode($mode);
        $compte = $this->targetUserId();

        if (! $mode || ! $compte) {
            return null;
        }

        $query = array_merge($request->query(), [
            'mode' => $mode,
            'compte' => $compte,
        ]);

        unset($query['exit_account_access']);

        return $request->url().'?'.http_build_query($query);
    }

    public function applyUrlDefaults(): void
    {
        $query = $this->buildQuery();

        if ($query !== []) {
            URL::defaults($query);
        }
    }

    private function recordAccessEntry(User $target, int $adminId, string $mode): void
    {
        $admin = User::find($adminId);
        $request = request();

        if ($mode === self::MODE_VIEW) {
            SecurityLog::log(
                $target->id,
                'admin_account_access_view',
                $request->ip(),
                $request->userAgent(),
                null,
                [
                    'admin_id' => $adminId,
                    'admin_name' => $admin?->name,
                    'mode' => self::MODE_VIEW,
                ],
                'low',
                false,
                'Un administrateur a consulté votre compte en mode lecture seule.'
            );

            return;
        }

        $config = match ($mode) {
            self::MODE_SUPPORT => [
                'event' => 'admin_account_access_support',
                'severity' => 'high',
                'description' => 'Un administrateur a accédé à votre compte en mode support.',
                'notify_message' => 'Un administrateur Allo Tata a accédé à votre compte en mode support le '.now()->format('d/m/Y à H:i').'. Il peut intervenir sur vos tickets et votre messagerie.',
            ],
            self::MODE_BILLING => [
                'event' => 'admin_account_access_billing',
                'severity' => 'high',
                'description' => 'Un administrateur a accédé à votre compte en mode facturation.',
                'notify_message' => 'Un administrateur Allo Tata a accédé à votre compte en mode facturation le '.now()->format('d/m/Y à H:i').'. Il peut gérer abonnements, paiements et finances.',
            ],
            default => [
                'event' => 'admin_account_access_edit',
                'severity' => 'high',
                'description' => 'Un administrateur a accédé à votre compte en mode édition.',
                'notify_message' => 'Un administrateur Allo Tata a accédé à votre compte en mode édition le '.now()->format('d/m/Y à H:i').'. Consultez l\'onglet Sécurité pour le détail des actions.',
            ],
        };

        SecurityLog::log(
            $target->id,
            $config['event'],
            $request->ip(),
            $request->userAgent(),
            null,
            [
                'admin_id' => $adminId,
                'admin_name' => $admin?->name,
                'mode' => $mode,
            ],
            $config['severity'],
            false,
            $config['description']
        );

        ActivityLog::create([
            'admin_id' => $adminId,
            'action' => 'login',
            'model_type' => User::class,
            'model_id' => $target->id,
            'description' => "Accès au compte {$target->email} (#{$target->id}) en mode {$mode}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $notifiedKey = $this->notifiedSessionKey($mode, $target->id);

        if (! session($notifiedKey)) {
            $this->notifications->notify(
                $target,
                NotificationPreferenceService::CATEGORY_SECURITY,
                'admin_account_access',
                'Accès administrateur à votre compte',
                $config['notify_message'],
                route('security.index'),
                ['mode' => $mode, 'admin_id' => $adminId],
                fn () => Mail::to($target->email)->send(new AdminAccountAccessMail($target, $mode)),
            );

            session([$notifiedKey => true]);
        }
    }

    private function notifiedSessionKey(string $mode, int $compte): string
    {
        return 'account_access_notified_'.strtolower($mode).'_'.$compte;
    }
}
