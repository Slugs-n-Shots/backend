<?php

namespace Tests\Unit;

use App\Http\Middleware\RequestLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class RequestLoggerTest extends TestCase
{
    public function test_request_logger_does_not_log_outside_local_or_testing_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        Log::shouldReceive('channel')->never();

        $request = Request::create('/api/guest/login', 'POST', [
            'email' => 'guest@example.com',
            'password' => 'Secret123!',
        ]);

        $response = (new RequestLogger())->handle($request, function () {
            return response()->json(['ok' => true]);
        });

        $this->assertSame(200, $response->getStatusCode());

        $this->app->detectEnvironment(fn () => 'testing');
    }

    public function test_request_logger_can_be_disabled_by_config(): void
    {
        config(['request_logger.enabled' => false]);

        Log::shouldReceive('channel')->never();

        $request = Request::create('/api/guest/login', 'POST', [
            'email' => 'guest@example.com',
            'password' => 'Secret123!',
        ]);

        $response = (new RequestLogger())->handle($request, function () {
            return response()->json(['ok' => true]);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_request_logger_masks_sensitive_request_and_response_fields_by_default(): void
    {
        config([
            'request_logger.enabled' => true,
            'request_logger.mask_sensitive' => true,
        ]);

        $messages = [];
        $logger = Mockery::mock();
        $logger->shouldReceive('info')
            ->twice()
            ->with(Mockery::on(function (string $message) use (&$messages) {
                $messages[] = $message;

                return true;
            }));

        Log::shouldReceive('channel')
            ->twice()
            ->with('requests')
            ->andReturn($logger);

        $request = Request::create('/api/guest/login', 'POST', [
            'email' => 'guest@example.com',
            'password' => 'Secret123!',
            'payer' => [
                'tax_number' => '12345678-2-42',
            ],
        ], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer secret-token',
        ]);

        (new RequestLogger())->handle($request, function () {
            return response()->json([
                'access_token' => 'secret-access-token',
                'user' => ['email' => 'guest@example.com'],
            ]);
        });

        $logged = implode("\n", $messages);

        $this->assertStringContainsString('[masked]', $logged);
        $this->assertStringNotContainsString('guest@example.com', $logged);
        $this->assertStringNotContainsString('Secret123!', $logged);
        $this->assertStringNotContainsString('12345678-2-42', $logged);
        $this->assertStringNotContainsString('secret-token', $logged);
        $this->assertStringNotContainsString('secret-access-token', $logged);
    }
}
