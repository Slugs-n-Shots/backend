<?php

namespace Tests\Unit;

use App\Http\Controllers\ReceiptController;
use App\Models\Employee;
use App\Models\Guest;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReceiptControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider validPaymentMethods
     */
    public function store_accepts_supported_payment_methods(string $paymentMethod)
    {
        $request = Request::create('/receipts', 'POST', $this->receiptPayload([
            'payment_method' => $paymentMethod,
        ]));

        $receipt = (new ReceiptController())->store($request);

        $this->assertSame($paymentMethod, $receipt->payment_method);
    }

    /**
     * @test
     * @dataProvider validPaymentMethods
     */
    public function update_accepts_supported_payment_methods(string $paymentMethod)
    {
        $receipt = Receipt::factory()->create();
        $request = Request::create("/receipts/{$receipt->id}", 'PUT', $this->receiptPayload([
            'payment_method' => $paymentMethod,
        ]));

        $updated = (new ReceiptController())->update($request, $receipt);

        $this->assertSame($paymentMethod, $updated->payment_method);
    }

    /**
     * @test
     * @dataProvider invalidPaymentMethods
     */
    public function store_rejects_unsupported_payment_methods(string $paymentMethod)
    {
        $request = Request::create('/receipts', 'POST', $this->receiptPayload([
            'payment_method' => $paymentMethod,
        ]));

        $this->expectException(ValidationException::class);

        (new ReceiptController())->store($request);
    }

    /**
     * @test
     * @dataProvider invalidPaymentMethods
     */
    public function update_rejects_unsupported_payment_methods(string $paymentMethod)
    {
        $receipt = Receipt::factory()->create();
        $request = Request::create("/receipts/{$receipt->id}", 'PUT', $this->receiptPayload([
            'payment_method' => $paymentMethod,
        ]));

        $this->expectException(ValidationException::class);

        (new ReceiptController())->update($request, $receipt);
    }

    /** @test */
    public function payment_method_name_is_localized()
    {
        $receipt = new Receipt(['payment_method' => Receipt::PAYMENT_METHOD_CASH]);

        App::setLocale('en');
        $this->assertSame('cash', $receipt->payment_method_name);

        App::setLocale('hu');
        $this->assertSame('készpénz', $receipt->payment_method_name);

        $receipt->payment_method = Receipt::PAYMENT_METHOD_CARD;
        $this->assertSame('kártyás', $receipt->payment_method_name);
    }

    public static function validPaymentMethods(): array
    {
        return [
            'cash' => [Receipt::PAYMENT_METHOD_CASH],
            'card' => [Receipt::PAYMENT_METHOD_CARD],
        ];
    }

    public static function invalidPaymentMethods(): array
    {
        return [
            'hungarian cash label' => ['készpénz'],
            'hungarian card label' => ['kártyás'],
            'unknown method' => ['transfer'],
        ];
    }

    private function receiptPayload(array $overrides = []): array
    {
        return array_merge([
            'serno' => fake()->unique()->numerify('T########'),
            'guest_id' => Guest::factory()->create()->id,
            'issued_at' => now()->toDateTimeString(),
            'paid_for' => Employee::factory()->create()->id,
            'paid_at' => now()->toDateTimeString(),
            'payment_method' => Receipt::PAYMENT_METHOD_CASH,
            'table' => null,
        ], $overrides);
    }
}
