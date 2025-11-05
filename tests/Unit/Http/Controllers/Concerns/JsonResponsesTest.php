<?php

namespace Tests\Unit\Http\Controllers\Concerns;

use App\Http\Controllers\Concerns\JsonResponses;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class JsonResponsesTest extends TestCase
{
    use JsonResponses;

    /** @test */
    public function success_returns_json_response_with_data()
    {
        $response = $this->success(['user' => 'John Doe'], 'Operation successful');
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'message' => 'Operation successful',
            'user' => 'John Doe',
        ], $response->getData(true));
    }

    /** @test */
    public function success_works_without_message()
    {
        $response = $this->success(['count' => 5]);
        
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['count' => 5], $response->getData(true));
    }

    /** @test */
    public function error_returns_error_response()
    {
        $response = $this->error('Something went wrong', 400);
        
        $this->assertEquals(400, $response->status());
        $this->assertEquals([
            'message' => 'Something went wrong',
        ], $response->getData(true));
    }

    /** @test */
    public function error_includes_additional_errors()
    {
        $response = $this->error('Validation issue', 422, ['field' => 'required']);
        
        $this->assertEquals(422, $response->status());
        $this->assertEquals([
            'message' => 'Validation issue',
            'errors' => ['field' => 'required'],
        ], $response->getData(true));
    }

    /** @test */
    public function validation_error_returns_422()
    {
        $errors = ['email' => 'Invalid email format'];
        $response = $this->validationError($errors);
        
        $this->assertEquals(422, $response->status());
        $this->assertEquals([
            'message' => 'Validation failed',
            'errors' => $errors,
        ], $response->getData(true));
    }

    /** @test */
    public function not_found_returns_404()
    {
        $response = $this->notFound('User not found');
        
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'User not found'], $response->getData(true));
    }

    /** @test */
    public function unauthorized_returns_401()
    {
        $response = $this->unauthorized('Invalid credentials');
        
        $this->assertEquals(401, $response->status());
        $this->assertEquals(['message' => 'Invalid credentials'], $response->getData(true));
    }

    /** @test */
    public function forbidden_returns_403()
    {
        $response = $this->forbidden('Access denied');
        
        $this->assertEquals(403, $response->status());
        $this->assertEquals(['message' => 'Access denied'], $response->getData(true));
    }

    /** @test */
    public function created_returns_201()
    {
        $response = $this->created(['id' => 1], 'User created');
        
        $this->assertEquals(201, $response->status());
        $this->assertEquals([
            'message' => 'User created',
            'id' => 1,
        ], $response->getData(true));
    }

    /** @test */
    public function updated_returns_200_with_message()
    {
        $response = $this->updated(['id' => 1], 'User updated');
        
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'message' => 'User updated',
            'id' => 1,
        ], $response->getData(true));
    }

    /** @test */
    public function deleted_returns_success_message()
    {
        $response = $this->deleted('User deleted');
        
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['message' => 'User deleted'], $response->getData(true));
    }

    /** @test */
    public function no_content_returns_204()
    {
        $response = $this->noContent();
        
        $this->assertEquals(204, $response->status());
        $this->assertEquals([], $response->getData(true));
    }
}
