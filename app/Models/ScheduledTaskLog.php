<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledTaskLog extends Model
{
    protected $fillable = [
        'command',
        'description',
        'status',
        'output',
        'exit_code',
        'duration_seconds',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_seconds' => 'decimal:2',
    ];

    // ── Scopes ──

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeForCommand($query, string $command)
    {
        return $query->where('command', $command);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ── Helpers ──

    public static function getCommandLabels(): array
    {
        return [
            'subscriptions:check-echeances' => 'Échéances abonnements Stripe',
            'subscriptions:generate-invoices' => 'Factures abonnements manuels',
            'subscriptions:process-payments' => 'Auto-charge & retry paiements',
            'subscriptions:reconcile-echeances' => 'Réconciliation Stripe',
            'essais:check-expiration' => 'Vérification essais gratuits',
            'reservations:send-reminders --hours=24' => 'Rappels RDV (24h)',
            'reservations:send-reminders --hours=2' => 'Rappels RDV (2h)',
            'reports:send-weekly' => 'Rapports hebdomadaires',
            'reports:send-monthly' => 'Rapports mensuels',
            'db:backup --keep=30' => 'Sauvegarde BDD',
            'invitations:nettoyer' => 'Nettoyage invitations',
            'presence:cleanup' => 'Nettoyage présences',
        ];
    }

    public function getLabelAttribute(): string
    {
        return self::getCommandLabels()[$this->command] ?? $this->command;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Succès</span>',
            'error' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Erreur</span>',
            'running' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">En cours</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">Inconnu</span>',
        };
    }

    /**
     * Nettoyer les anciens logs (garder les 30 derniers jours)
     */
    public static function cleanup(int $days = 30): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
