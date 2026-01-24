<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceOption;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'base_price' => 'required|numeric',
            'base_duration' => 'required|integer',
            'has_options' => 'boolean',
            'options' => 'nullable|array'
        ]);

        $service = Service::create($validated);

        if ($request->has_options && $request->options) {
            foreach ($request->options as $optionData) {
                $option = $service->options()->create([
                    'name' => $optionData['name'],
                    'type' => $optionData['type'],
                    'is_required' => $optionData['is_required'] ?? false
                ]);

                foreach ($optionData['choices'] as $choiceData) {
                    $option->choices()->create([
                        'value' => $choiceData['value'],
                        'price_modifier' => $choiceData['price_modifier'],
                        'duration_modifier' => $choiceData['duration_modifier']
                    ]);
                }
            }
        }

        return response()->json($service->load('options.choices'), 201);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'string',
            'base_price' => 'numeric',
            'base_duration' => 'integer',
            'has_options' => 'boolean',
            'options' => 'nullable|array'
        ]);

        $service->update($validated);

        if ($request->has('options')) {
            $service->options()->delete(); // Supprime les anciennes options
            
            foreach ($request->options as $optionData) {
                $option = $service->options()->create([
                    'name' => $optionData['name'],
                    'type' => $optionData['type'],
                    'is_required' => $optionData['is_required'] ?? false
                ]);

                foreach ($optionData['choices'] as $choiceData) {
                    $option->choices()->create($choiceData);
                }
            }
        }

        return response()->json($service->load('options.choices'));
    }
}