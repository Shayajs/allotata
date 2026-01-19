<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /**
     * Afficher la liste des templates
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('type')->get();
        
        return view('admin.email-templates.index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Afficher un template pour édition
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
        ]);
    }

    /**
     * Mettre à jour un template
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $emailTemplate->update($validated);

        return redirect()->route('admin.email-templates.index')
            ->with('success', 'Template mis à jour avec succès.');
    }

    /**
     * Prévisualiser un template avec des données d'exemple
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        // Données d'exemple pour la prévisualisation
        $exampleData = [];
        if ($emailTemplate->variables) {
            foreach ($emailTemplate->variables as $variable) {
                $exampleData[$variable] = "Exemple de {$variable}";
            }
        }

        $replaced = $emailTemplate->replaceVariables($exampleData);

        return view('emails.template', [
            'subject' => $replaced['subject'],
            'body' => $replaced['body'],
            'template' => $emailTemplate,
        ]);
    }
}
