<?php

namespace App\Services;

use App\Models\SiteDailyVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SiteTrafficService
{
    public function __construct(
        private BotDetector $botDetector
    ) {}

    public function record(Request $request): void
    {
        if (! Schema::hasTable('site_daily_visitors') || ! $this->shouldTrack($request)) {
            return;
        }

        $sessionId = $request->session()->getId();
        if ($sessionId === '') {
            return;
        }

        $visitDate = now()->toDateString();
        $userId = Auth::id();
        $isBot = $this->botDetector->isBotRequest($request);

        $visitorType = match (true) {
            $isBot => SiteDailyVisitor::TYPE_BOT,
            $userId !== null => SiteDailyVisitor::TYPE_MEMBER,
            default => SiteDailyVisitor::TYPE_GUEST,
        };

        $ipHash = hash('sha256', $request->ip().'|'.config('app.key'));

        try {
            $visitor = SiteDailyVisitor::query()
                ->where('visit_date', $visitDate)
                ->where('session_id', $sessionId)
                ->first();

            if ($visitor) {
                $visitor->increment('page_views');

                if ($userId && $visitor->visitor_type !== SiteDailyVisitor::TYPE_BOT) {
                    $visitor->update([
                        'user_id' => $userId,
                        'visitor_type' => SiteDailyVisitor::TYPE_MEMBER,
                    ]);
                }

                return;
            }

            SiteDailyVisitor::create([
                'visit_date' => $visitDate,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'visitor_type' => $visitorType,
                'page_views' => 1,
                'ip_hash' => $ipHash,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        if ($request->is(
            'up',
            'health',
            'stripe/*',
            'webhooks/*',
            'broadcasting/auth',
            'api/*',
            'livewire/*',
            '_debugbar/*'
        )) {
            return false;
        }

        if ($request->routeIs(
            'api.*',
            'storage.serve',
            'manifest.*',
            'cron.*'
        )) {
            return false;
        }

        $path = $request->path();
        if (preg_match('/\.(css|js|map|ico|png|jpe?g|gif|webp|svg|woff2?|ttf|eot)$/i', $path)) {
            return false;
        }

        return true;
    }
}
