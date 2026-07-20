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

    public const SESSION_ADMIN_ID = 'account_access_admin_id';

    public const SESSION_MODE = 'account_access_mode';

    public const SESSION_COMPTE = 'account_access_compte';

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

    public function shouldExitOnGet(Request $request): bool
    {
        if (! $request->isMethodSafe()) {
            return false;
        }

        if ($request->query('mode') !== null || $request->query('compte') !== null) {
            return false;
        }

        return $this->hasBridge();
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

    public function canWrite(): bool
    {
        return $this->isActive() && $this->mode() === self::MODE_EDIT;
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
        $compte = $this->targetUserId();

        session()->forget([
            self::SESSION_ADMIN_ID,
            self::SESSION_MODE,
            self::SESSION_COMPTE,
        ]);

        if ($compte) {
            session()->forget($this->notifiedSessionKey($compte));
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

        SecurityLog::log(
            $target->id,
            'admin_account_access_edit',
            $request->ip(),
            $request->userAgent(),
            null,
            [
                'admin_id' => $adminId,
                'admin_name' => $admin?->name,
                'mode' => self::MODE_EDIT,
            ],
            'high',
            false,
            'Un administrateur a accédé à votre compte en mode édition.'
        );

        ActivityLog::create([
            'admin_id' => $adminId,
            'action' => 'login',
            'model_type' => User::class,
            'model_id' => $target->id,
            'description' => "Accès au compte {$target->email} (#{$target->id}) en mode EDIT",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $notifiedKey = $this->notifiedSessionKey($target->id);

        if (! session($notifiedKey)) {
            $this->notifications->notify(
                $target,
                NotificationPreferenceService::CATEGORY_SECURITY,
                'admin_account_access',
                'Accès administrateur à votre compte',
                'Un administrateur Allo Tata a accédé à votre compte en mode édition le '.now()->format('d/m/Y à H:i').'. Consultez l\'onglet Sécurité pour le détail des actions.',
                route('security.index'),
                ['mode' => self::MODE_EDIT, 'admin_id' => $adminId],
                fn () => Mail::to($target->email)->send(new AdminAccountAccessMail($target)),
            );

            session([$notifiedKey => true]);
        }
    }

    private function notifiedSessionKey(int $compte): string
    {
        return 'account_access_notified_edit_'.$compte;
    }
}
