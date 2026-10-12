<?php

namespace Tests\Unit;

use App\Models\Farm;
use App\Models\Meal;
use Tests\TestCase;

class DisplayLabelsTest extends TestCase
{
    public function test_meal_types_render_translated_labels(): void
    {
        app()->setLocale('fr');

        $this->assertSame('Petit-déjeuner', Meal::typeLabel('breakfast'));
        $this->assertSame('Déjeuner', Meal::typeLabel('lunch'));

        app()->setLocale('ar');

        $this->assertSame('فطور', Meal::typeLabel('breakfast'));
    }

    public function test_meal_type_values_stay_english(): void
    {
        $this->assertSame(['breakfast', 'lunch', 'dinner', 'snack', 'other'], Meal::TYPES);
    }

    public function test_farming_types_render_translated_labels(): void
    {
        app()->setLocale('fr');

        $this->assertSame('Bio', Farm::farmingTypeLabel('Organic'));

        app()->setLocale('ar');

        $this->assertSame('مستدام', Farm::farmingTypeLabel('Sustainable'));
    }

    public function test_farming_type_values_stay_english(): void
    {
        $this->assertSame(['Organic', 'Sustainable', 'Traditional', 'Biodynamic'], Farm::FARMING_TYPES);
    }
}
