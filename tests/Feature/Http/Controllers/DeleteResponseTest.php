<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\DrinkCategoryController;
use App\Http\Controllers\DrinkUnitController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\PromoTypeController;
use App\Http\Controllers\ReceiptController;
use App\Models\DrinkCategory;
use App\Models\DrinkUnit;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PromoType;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteResponseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function order_destroy_returns_no_content_response()
    {
        // Arrange
        $order = Order::factory()->create();

        // Act
        $controller = new OrderController();
        $response = $controller->destroy($order);

        // Assert
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    /** @test */
    public function drink_unit_destroy_returns_no_content_response()
    {
        // Arrange
        $drinkUnit = DrinkUnit::factory()->create();

        // Act
        $controller = new DrinkUnitController();
        $response = $controller->destroy($drinkUnit);

        // Assert
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    /** @test */
    public function order_detail_destroy_returns_no_content_response()
    {
        // Arrange
        $orderDetail = OrderDetail::factory()->create();

        // Act
        $controller = new OrderDetailController();
        $response = $controller->destroy($orderDetail);

        // Assert
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    /** @test */
    public function promo_type_destroy_returns_no_content_response()
    {
        // Arrange
        $promoType = PromoType::factory()->create();

        // Act
        $controller = new PromoTypeController();
        $response = $controller->destroy($promoType);

        // Assert
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    /** @test */
    public function receipt_destroy_returns_no_content_response()
    {
        // Arrange
        $receipt = Receipt::factory()->create();

        // Act
        $controller = new ReceiptController();
        $response = $controller->destroy($receipt);

        // Assert
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    /** @test */
    public function drink_category_destroy_returns_no_content_response()
    {
        // Arrange
        $drinkCategory = DrinkCategory::factory()->create();

        // Act
        $controller = new DrinkCategoryController();
        $response = $controller->destroy($drinkCategory->id);

        // Assert
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }
}
