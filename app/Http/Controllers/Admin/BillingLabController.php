<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BillingLab\BillingLabGuard;
use App\Services\BillingLab\LabFixtures;
use App\Services\BillingLab\LocalEvidenceProbe;
use App\Services\BillingLab\ScenarioRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use RuntimeException;

class BillingLabController extends Controller
{
    public function index(ScenarioRunner $runner, LocalEvidenceProbe $probe): View
    {
        return view('admin.billing-lab', [
            'mode' => BillingLabGuard::mode(),
            'liveBlocked' => BillingLabGuard::isLiveMode(),
            'canCallStripe' => BillingLabGuard::canCallStripe(),
            'catalog' => collect($runner->catalog())->map(fn ($scenario) => [
                'id' => $scenario->id(),
                'label' => $scenario->label(),
                'group' => $scenario->group(),
                'requires_stripe_live' => $scenario->requiresStripeLive(),
            ])->groupBy('group'),
            'evidence' => $probe->run(),
            'lastReport' => Cache::get(ScenarioRunner::CACHE_KEY),
        ]);
    }

    public function run(Request $request, ScenarioRunner $runner): JsonResponse
    {
        $data = $request->validate([
            'scenario' => ['required', 'string', 'max:64'],
            'allow_live' => ['sometimes', 'boolean'],
        ]);

        try {
            if ($request->boolean('allow_live')) {
                BillingLabGuard::assertNotLive();
            }
            $result = $runner->run($data['scenario'], (bool) $request->boolean('allow_live'));
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'status' => 'fail',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }

    public function runAll(Request $request, ScenarioRunner $runner): JsonResponse
    {
        try {
            if ($request->boolean('allow_live')) {
                BillingLabGuard::assertNotLive();
            }
            $report = $runner->runAll((bool) $request->boolean('allow_live'));
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json($report);
    }

    public function cleanup(LabFixtures $fixtures): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'message' => 'Fixtures billing-lab nettoyées.',
            'details' => $fixtures->cleanup(),
        ]);
    }

    public function report(LocalEvidenceProbe $probe): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'mode' => BillingLabGuard::mode(),
            'evidence' => $probe->run(),
            'last_run' => Cache::get(ScenarioRunner::CACHE_KEY),
        ]);
    }
}
