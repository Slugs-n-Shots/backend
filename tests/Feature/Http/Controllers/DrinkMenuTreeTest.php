<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\DrinkCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DrinkMenuTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_tree_returns_category_localized_name_fields(): void
    {
        Cache::flush();

        $parent = DrinkCategory::factory()->create([
            'name_en' => 'Coffee',
            'name_hu' => 'Kávé',
        ]);

        $child = DrinkCategory::factory()->create([
            'name_en' => 'Espresso',
            'name_hu' => 'Eszpresszó',
            'parent_id' => $parent->id,
        ]);

        $response = $this->getJson('/api/guest/menu-tree');

        $response->assertOk();

        $menuTree = $response->json();

        $this->assertSame('Coffee', $menuTree[$parent->id]['name_en']);
        $this->assertSame('Kávé', $menuTree[$parent->id]['name_hu']);
        $this->assertSame('Espresso', $menuTree[$parent->id]['subcategory'][$child->id]['name_en']);
        $this->assertSame('Eszpresszó', $menuTree[$parent->id]['subcategory'][$child->id]['name_hu']);
    }
}
