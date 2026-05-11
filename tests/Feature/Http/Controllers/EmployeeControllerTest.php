<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\EmployeeController;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function index_uses_all_instead_of_get()
    {
        // Arrange
        $employees = Employee::factory()->count(3)->create();

        $request = Request::create('/employees', 'GET');

        // Act
        $controller = new EmployeeController();
        $response = $controller->index($request);

        // Assert
        $this->assertEmpty($employees->pluck('id')->diff($response->pluck('id')));
        $this->assertContains('created_at', array_keys($response->first()->toArray()));
    }

    /** @test */
    public function index_returns_empty_collection_when_no_employees()
    {
        // Arrange
        Employee::query()->delete();
        $request = Request::create('/employees', 'GET');

        // Act
        $controller = new EmployeeController();
        $response = $controller->index($request);

        // Assert
        $this->assertCount(0, $response);
    }
}
