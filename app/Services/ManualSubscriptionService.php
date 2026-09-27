<?php

namespace App\Services;

use App\Models\AbonnementManuelEvenement;
use App\Models\AbonnementManuelPeriode;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Abonnement manuel sans date de fin.
 * Une échéance non confirmée ne suspend jamais le membre.
 * Seul un refus explicite d'administrateur retire les avantages.
 */
class ManualSubscriptionService
{
    public const OVERDUE_DAYS = 3;

    public function cycleOf(User $user): string
    {
        return $user->abonnement_manuel_type_renouvellement === 'annuel' ? 'year' : 'month';
    }

    public function syncAllActive(bool $notify = true): int
    {
        $created = 0;

        User::query()
            ->withActiveManualPremium()
            ->whereNotNull('abonnement_manuel_date_debut')
            ->orderBy('id')
            ->each(function (User $user) use (&$created, $notify) {
                $created += $this->syncUser($user, $notify);
            });

        return $created;
    }

    public function syncUser(User $user, bool $notify = true): int
    {
        if (! $user->hasActiveManualPremium() || ! $user->abonnement_manuel_date_debut) {
            return 0;
        }

        $start = $user->abonnement_manuel_date_debut->copy()->startOfDay();
        $today = now()->startOfDay();
        if ($start->gt($today)) {
            return 0;
        }

        $cycle = $this->cycleOf($user);
        $dues = ManualSubscriptionCalendar::dueDatesUntil($start, $today, $cycle);
        $created = 0;

        foreach ($dues as $index => $due) {
            $next = ManualSubscriptionCalendar::anniversary($start, $index + 2, $cycle);

            try {
                $period = AbonnementManuelPeriode::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'echeance_at' => $due->toDateString(),
                    ],
                    [
                        'periode_debut' => $due->toDateString(),
                        'periode_fin' => $next->copy()->subDay()->toDateString(),
                        'statut' => AbonnementManuelPeriode::PENDING,
                    ]
                );
            } catch (UniqueConstraintViolationException) {
                continue;
            }

            if (! $period->wasRecentlyCreated) {
                continue;
            }

            $created++;
            $this->record(
                $user,
                AbonnementManuelEvenement::DUE_CREATED,
                'Échéance du '.$due->format('d/m/Y').' ouverte. Le membre reste actif.',
                $period,
                null,
            );

            $window = $cycle === 'year' ? 366 : 35;
            if ($notify && ManualSubscriptionCalendar::calendarDaysBetween($due, $today) <= $window) {
                $this->notifyDue($period, $user);
                if ($this->daysLate($period) >= self::OVERDUE_DAYS) {
                    $this->notifyOverdue($period, $user);
                }
            }
        }

        if ($notify) {
            AbonnementManuelPeriode::query()
                ->where('user_id', $user->id)
                ->where('statut', AbonnementManuelPeriode::PENDING)
                ->whereNull('notified_overdue_at')
                ->whereDate('echeance_at', '<=', $today->copy()->subDays(self::OVERDUE_DAYS)->toDateString())
                ->whereDate('echeance_at', '>=', $today->copy()->subDays($cycle === 'year' ? 366 : 35)->toDateString())
                ->each(fn (AbonnementManuelPeriode $period) => $this->notifyOverdue($period, $user));
        }

        return $created;
    }

    /**
     * @return array{level: string, echeance: Carbon, days: int, period: ?AbonnementManuelPeriode, month: string}|null
     */
    public function attention(User $user): ?array
    {
        if (! $user->abonnement_manuel || ! $user->abonnement_manuel_date_debut) {
            return null;
        }

        if ($user->abonnement_manuel_statut === User::MANUAL_STATUS_CANCELLED) {
            $period = $user->abonnementManuelPeriodes()
                ->where('statut', AbonnementManuelPeriode::NOT_PAID)
                ->orderByDesc('echeance_at')
                ->first();

            $echeance = $period?->echeance_at ?? $user->abonnement_manuel_date_debut;

            return [
                'level' => 'cancelled',
                'echeance' => $echeance->copy()->startOfDay(),
                'days' => $this->daysLateFrom($echeance),
                'period' => $period,
                'month' => $echeance->copy()->locale('fr')->translatedFormat('F Y'),
            ];
        }

        if (! $user->hasActiveManualPremium()) {
            return null;
        }

        $period = $user->abonnementManuelPeriodes()
            ->whereIn('statut', [AbonnementManuelPeriode::PENDING, AbonnementManuelPeriode::MEMBER_DECLARED])
            ->whereDate('echeance_at', '<=', now()->toDateString())
            ->orderBy('echeance_at')
            ->first();

        if (! $period) {
            $computed = $this->oldestUnrecordedDue($user);
            if (! $computed) {
                return null;
            }

            return [
                'level' => $this->daysLateFrom($computed) >= self::OVERDUE_DAYS ? 'red' : 'yellow',
                'echeance' => $computed,
                'days' => $this->daysLateFrom($computed),
                'period' => null,
                'month' => $computed->copy()->locale('fr')->translatedFormat('F Y'),
            ];
        }

        $days = $this->daysLate($period);

        if ($period->statut === AbonnementManuelPeriode::MEMBER_DECLARED) {
            $olderPending = $user->abonnementManuelPeriodes()
                ->where('statut', AbonnementManuelPeriode::PENDING)
                ->whereDate('echeance_at', '<=', now()->toDateString())
                ->orderBy('echeance_at')
                ->first();

            if ($olderPending) {
                $period = $olderPending;
                $days = $this->daysLate($period);
            } else {
                return [
                    'level' => 'declared',
                    'echeance' => $period->echeance_at->copy(),
                    'days' => $days,
                    'period' => $period,
                    'month' => $period->libelleMois(),
                ];
            }
        }

        return [
            'level' => $days >= self::OVERDUE_DAYS ? 'red' : 'yellow',
            'echeance' => $period->echeance_at->copy(),
            'days' => $days,
            'period' => $period,
            'month' => $period->libelleMois(),
        ];
    }

    /**
     * @param  array{date_debut: string, type_renouvellement: string, montant: numeric, notes?: ?string}  $data
     */
    public function activate(User $user, array $data, ?User $actor = null): void
    {
        $debut = Carbon::parse($data['date_debut'])->startOfDay();
        $wasInactive = ! $user->hasActiveManualPremium();

        $user->update([
            'abonnement_manuel' => true,
            'abonnement_manuel_statut' => User::MANUAL_STATUS_ACTIVE,
            'abonnement_manuel_actif_jusqu' => null,
            'abonnement_manuel_notes' => $data['notes'] ?? null,
            'abonnement_manuel_type_renouvellement' => $data['type_renouvellement'],
            'abonnement_manuel_jour_renouvellement' => $debut->day,
            'abonnement_manuel_date_debut' => $debut->toDateString(),
            'abonnement_manuel_montant' => $data['montant'],
        ]);

        $user->refresh();

        if ($wasInactive) {
            $this->record(
                $user,
                AbonnementManuelEvenement::STARTED,
                'Abonnement manuel activé le '.$debut->format('d/m/Y').', sans date de fin.',
                null,
                $actor,
            );
            $this->notifyMember(
                $user,
                'manuel_abonnement_actif',
                'Abonnement manuel actif',
                'Votre abonnement manuel est actif, sans date de fin. À chaque échéance, l\'administration vérifie le paiement. Votre accès reste ouvert tant que ce paiement n\'est pas explicitement refusé.',
            );
        }

        $this->syncUser($user, true);
    }

    public function revokeByAdmin(User $user, ?User $actor = null): bool
    {
        if (! $user->abonnement_manuel || ! $user->hasActiveManualPremium()) {
            return false;
        }

        $user->update([
            'abonnement_manuel_statut' => User::MANUAL_STATUS_ENDED,
        ]);

        $this->record(
            $user,
            AbonnementManuelEvenement::REVOKED,
            'Abonnement manuel arrêté par l\'administration. L\'historique est conservé.',
            null,
            $actor,
        );

        $this->notifyMember(
            $user,
            'manuel_abonnement_arrete',
            'Abonnement manuel arrêté',
            'Votre abonnement manuel a été arrêté par l\'administration. Les avantages associés sont retirés. Votre compte et l\'historique de vos échéances sont conservés.',
        );

        return true;
    }

    public function confirm(AbonnementManuelPeriode $period, User $admin): string
    {
        return DB::transaction(function () use ($period, $admin) {
            $period = AbonnementManuelPeriode::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            if ($period->statut === AbonnementManuelPeriode::CONFIRMED) {
                return 'already';
            }

            if (! $period->estOuverte()) {
                return 'invalid';
            }

            $user = User::query()->whereKey($period->user_id)->lockForUpdate()->firstOrFail();
            if (! $user->hasActiveManualPremium()) {
                return 'inactive';
            }

            $period->update([
                'statut' => AbonnementManuelPeriode::CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => $admin->id,
            ]);

            $label = $period->libelleMois();
            $this->record(
                $user,
                AbonnementManuelEvenement::CONFIRMED,
                'Paiement '.$label.' confirmé le '.now()->format('d/m/Y').' par '.$admin->name.'.',
                $period,
                $admin,
                ['confirmed_at' => now()->toIso8601String()],
            );

            $this->notifyMember(
                $user,
                'manuel_paiement_confirme',
                'Paiement manuel confirmé',
                'Votre paiement manuel de '.$label.' (échéance du '.$period->echeance_at->format('d/m/Y').') a été confirmé. Votre abonnement reste actif.',
                ['periode_id' => $period->id],
            );

            return 'confirmed';
        });
    }

    public function refuse(AbonnementManuelPeriode $period, User $admin): string
    {
        return DB::transaction(function () use ($period, $admin) {
            $period = AbonnementManuelPeriode::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            $user = User::query()->whereKey($period->user_id)->lockForUpdate()->firstOrFail();

            if ($period->statut === AbonnementManuelPeriode::NOT_PAID && $user->abonnement_manuel_statut === User::MANUAL_STATUS_CANCELLED) {
                return 'already';
            }

            if ($period->statut === AbonnementManuelPeriode::CONFIRMED || ! $period->estOuverte()) {
                return 'invalid';
            }

            $period->update([
                'statut' => AbonnementManuelPeriode::NOT_PAID,
                'refused_at' => now(),
                'refused_by' => $admin->id,
            ]);

            $user->update([
                'abonnement_manuel' => true,
                'abonnement_manuel_statut' => User::MANUAL_STATUS_CANCELLED,
            ]);

            $label = $period->libelleMois();
            $this->record(
                $user,
                AbonnementManuelEvenement::REFUSED,
                'Paiement '.$label.' marqué non réalisé le '.now()->format('d/m/Y').' par '.$admin->name.'. Abonnement annulé.',
                $period,
                $admin,
            );

            $this->notifyMember(
                $user,
                'manuel_abonnement_annule',
                'Abonnement manuel annulé',
                'Votre abonnement manuel a été annulé car le paiement de l\'échéance du '.$period->echeance_at->format('d/m/Y').' n\'a pas été confirmé. Les avantages associés sont retirés. Votre compte et votre historique sont conservés. Si vous avez effectué le paiement, signalez-le depuis votre espace : votre accès sera rétabli immédiatement.',
                ['periode_id' => $period->id],
            );

            $this->notifyAdmins(
                'admin_manuel_annule',
                'Abonnement manuel annulé',
                $user->full_name.' — paiement '.$label.' marqué non réalisé. Les avantages manuels sont retirés.',
                ['periode_id' => $period->id, 'user_id' => $user->id],
                $admin->id,
            );

            return 'refused';
        });
    }

    public function declarePaid(User $member): string
    {
        return DB::transaction(function () use ($member) {
            $member = User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if (! $member->abonnement_manuel || ! $member->abonnement_manuel_date_debut) {
                return 'none';
            }

            if ($member->abonnement_manuel_statut === User::MANUAL_STATUS_CANCELLED) {
                $period = AbonnementManuelPeriode::query()
                    ->where('user_id', $member->id)
                    ->where('statut', AbonnementManuelPeriode::NOT_PAID)
                    ->orderByDesc('echeance_at')
                    ->lockForUpdate()
                    ->first();

                if (! $period) {
                    return 'none';
                }

                $period->update([
                    'statut' => AbonnementManuelPeriode::MEMBER_DECLARED,
                    'declared_at' => now(),
                ]);

                $member->update([
                    'abonnement_manuel_statut' => User::MANUAL_STATUS_ACTIVE,
                ]);

                $label = $period->libelleMois();
                $this->record(
                    $member,
                    AbonnementManuelEvenement::REACTIVATED,
                    'Paiement '.$label.' déclaré par le membre. Abonnement réactivé, vérification administrative en attente.',
                    $period,
                    $member,
                );

                $this->notifyMember(
                    $member,
                    'manuel_paiement_declare',
                    'Paiement signalé, accès rétabli',
                    'Votre déclaration a été enregistrée. Votre abonnement manuel est de nouveau actif. L\'administration va vérifier le paiement de l\'échéance du '.$period->echeance_at->format('d/m/Y').'.',
                    ['periode_id' => $period->id],
                );

                $this->notifyAdmins(
                    'admin_manuel_declare',
                    'Paiement manuel déclaré',
                    $member->full_name.' a déclaré avoir effectué son paiement manuel (échéance du '.$period->echeance_at->format('d/m/Y').'). L\'accès a été rétabli, une vérification est nécessaire.',
                    ['periode_id' => $period->id, 'user_id' => $member->id],
                );

                return 'reactivated';
            }

            if (! $member->hasActiveManualPremium()) {
                return 'inactive';
            }

            $this->syncUser($member, false);
            $member->refresh();

            $recentDeclaration = AbonnementManuelPeriode::query()
                ->where('user_id', $member->id)
                ->where('statut', AbonnementManuelPeriode::MEMBER_DECLARED)
                ->where('declared_at', '>=', now()->subSeconds(15))
                ->exists();

            if ($recentDeclaration) {
                return 'already';
            }

            $period = AbonnementManuelPeriode::query()
                ->where('user_id', $member->id)
                ->where('statut', AbonnementManuelPeriode::PENDING)
                ->whereDate('echeance_at', '<=', now()->toDateString())
                ->orderBy('echeance_at')
                ->lockForUpdate()
                ->first();

            if (! $period) {
                $alreadyDeclared = AbonnementManuelPeriode::query()
                    ->where('user_id', $member->id)
                    ->where('statut', AbonnementManuelPeriode::MEMBER_DECLARED)
                    ->exists();

                return $alreadyDeclared ? 'already' : 'none';
            }

            $period->update([
                'statut' => AbonnementManuelPeriode::MEMBER_DECLARED,
                'declared_at' => now(),
            ]);

            $label = $period->libelleMois();
            $this->record(
                $member,
                AbonnementManuelEvenement::DECLARED,
                'Paiement '.$label.' déclaré par le membre. Vérification administrative nécessaire. Le membre reste actif.',
                $period,
                $member,
            );

            $this->notifyMember(
                $member,
                'manuel_paiement_declare',
                'Paiement signalé',
                'Votre déclaration de paiement pour l\'échéance du '.$period->echeance_at->format('d/m/Y').' a été transmise à l\'administration. Votre accès reste actif.',
                ['periode_id' => $period->id],
            );

            $this->notifyAdmins(
                'admin_manuel_declare',
                'Paiement manuel déclaré',
                $member->full_name.' a déclaré avoir effectué son paiement manuel (échéance du '.$period->echeance_at->format('d/m/Y').').',
                ['periode_id' => $period->id, 'user_id' => $member->id],
            );

            return 'declared';
        });
    }

    public function countMembersNeedingReview(): int
    {
        return (int) AbonnementManuelPeriode::query()
            ->needingReview()
            ->distinct()
            ->count('user_id');
    }

    public function daysLate(AbonnementManuelPeriode $period): int
    {
        return $this->daysLateFrom($period->echeance_at);
    }

    public function daysLateFrom(Carbon $echeance): int
    {
        return ManualSubscriptionCalendar::calendarDaysBetween($echeance, now());
    }

    private function oldestUnrecordedDue(User $user): ?Carbon
    {
        $start = $user->abonnement_manuel_date_debut->copy()->startOfDay();
        $dues = ManualSubscriptionCalendar::dueDatesUntil($start, now(), $this->cycleOf($user));
        $known = $user->abonnementManuelPeriodes()->pluck('echeance_at')->map(fn ($date) => $date->toDateString())->all();

        foreach ($dues as $due) {
            if (! in_array($due->toDateString(), $known, true)) {
                return $due;
            }
        }

        return null;
    }

    private function notifyDue(AbonnementManuelPeriode $period, User $user): void
    {
        $claimed = AbonnementManuelPeriode::query()
            ->whereKey($period->id)
            ->whereNull('notified_due_at')
            ->update(['notified_due_at' => now()]);

        if ($claimed !== 1) {
            return;
        }

        $label = $period->echeance_at->format('d/m/Y');
        $this->notifyMember(
            $user,
            'manuel_echeance_attendue',
            'Paiement manuel à vérifier',
            'Votre échéance du '.$label.' est arrivée. Votre accès reste actif. Si vous avez effectué le paiement, signalez-le depuis votre espace membre.',
            ['periode_id' => $period->id],
        );

        $this->notifyAdmins(
            'admin_manuel_a_verifier',
            'Paiement manuel à vérifier',
            $user->full_name.' — échéance du '.$label.'. Le membre reste actif.',
            ['periode_id' => $period->id, 'user_id' => $user->id],
        );
    }

    private function notifyOverdue(AbonnementManuelPeriode $period, User $user): void
    {
        if ($period->statut !== AbonnementManuelPeriode::PENDING) {
            $period->refresh();
        }

        if ($period->statut !== AbonnementManuelPeriode::PENDING) {
            return;
        }

        $claimed = AbonnementManuelPeriode::query()
            ->whereKey($period->id)
            ->where('statut', AbonnementManuelPeriode::PENDING)
            ->whereNull('notified_overdue_at')
            ->update(['notified_overdue_at' => now()]);

        if ($claimed !== 1) {
            return;
        }

        $days = $this->daysLate($period);
        $label = $period->echeance_at->format('d/m/Y');

        $this->notifyMember(
            $user,
            'manuel_echeance_retard',
            'Paiement manuel non confirmé',
            'Votre paiement manuel de l\'échéance du '.$label.' n\'a pas encore été confirmé ('.$days.' jours). Votre accès reste actif. Si vous avez déjà payé, signalez-le ou contactez l\'administration.',
            ['periode_id' => $period->id, 'jours' => $days],
        );

        $this->notifyAdmins(
            'admin_manuel_retard',
            'Paiement manuel en attente',
            $user->full_name.' — échéance du '.$label.' non confirmée depuis '.$days.' jours. Le membre reste actif tant que le paiement n\'est pas marqué non réalisé.',
            ['periode_id' => $period->id, 'user_id' => $user->id, 'jours' => $days],
        );
    }

    private function notifyMember(User $user, string $type, string $titre, string $message, array $donnees = []): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'lien' => route('settings.index', ['tab' => 'subscription']),
            'donnees' => $donnees,
        ]);
    }

    private function notifyAdmins(string $type, string $titre, string $message, array $donnees = [], ?int $excludeUserId = null): void
    {
        $lien = route('admin.manual-subscriptions.index');

        User::query()
            ->where('is_admin', true)
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->orderBy('id')
            ->each(function (User $admin) use ($type, $titre, $message, $lien, $donnees) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => $type,
                    'titre' => $titre,
                    'message' => $message,
                    'lien' => $lien,
                    'donnees' => $donnees,
                ]);
            });
    }

    private function record(User $user, string $type, string $message, ?AbonnementManuelPeriode $period, ?User $actor, array $metadata = []): void
    {
        AbonnementManuelEvenement::create([
            'user_id' => $user->id,
            'abonnement_manuel_periode_id' => $period?->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }
}
