<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunSiteAudit;
use App\Models\SiteAudit;
use Illuminate\Http\Request;

class SiteAuditController extends Controller
{
    public function index()
    {
        $audits = SiteAudit::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.audits.index', compact('audits'));
    }

    public function show(SiteAudit $audit)
    {
        $audit->load('user');
        $previousAudit = $audit->getPreviousAudit();

        return view('admin.audits.show', compact('audit', 'previousAudit'));
    }

    public function start(Request $request)
    {
        $running = SiteAudit::where('status', 'running')->exists();

        if ($running) {
            return back()->with('error', 'Un audit est déjà en cours d\'exécution.');
        }

        $audit = SiteAudit::create([
            'user_id' => auth()->id(),
            'status' => 'running',
            'started_at' => now(),
        ]);

        RunSiteAudit::dispatch($audit->id);

        return redirect()->route('admin.audits.show', $audit)
            ->with('success', 'Audit lancé ! Vous pouvez quitter cette page — une notification vous sera envoyée.');
    }
}
