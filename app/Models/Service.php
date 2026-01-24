<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'base_price', 'base_duration', 'has_options'];

    public function options()
    {
        return $this->hasMany(ServiceOption::class);
    }

    public function calculateFinalPrice(array $selectedOptions)
    {
        $finalPrice = $this->base_price;
        foreach ($selectedOptions as $optionId => $choiceId) {
            $choice = ServiceOptionChoice::find($choiceId);
            $finalPrice += $choice->price_modifier;
        }
        return $finalPrice;
    }
}