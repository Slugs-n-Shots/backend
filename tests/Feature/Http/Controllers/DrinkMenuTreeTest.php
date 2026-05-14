<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrinkMenuTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_tree_returns_category_localized_name_fields(): void
    {
        $this->markTestSkipped(
            'TODO: menu-tree localization/cache behavior depends on the future drink menu caching decision.'
        );
    }
}
