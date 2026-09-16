<?php
namespace Tests\Unit;

use App\Core\Validator;
use App\Exceptions\ValidationException;
use Tests\Support\UnitTestCase;

class ValidatorTest extends UnitTestCase
{
    public function test_required_passes_when_filled(): void
    {
        $result = Validator::make(['name' => 'Budi'], ['name' => 'required']);
        $this->assertSame('Budi', $result['name']);
    }

    public function test_required_fails_when_missing(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make([], ['name' => 'required']);
    }

    public function test_required_fails_when_empty(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['name' => ''], ['name' => 'required']);
    }

    public function test_email_valid(): void
    {
        $result = Validator::make(['email' => 'a@b.com'], ['email' => 'email']);
        $this->assertSame('a@b.com', $result['email']);
    }

    public function test_email_invalid(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['email' => 'not-email'], ['email' => 'email']);
    }

    public function test_min_string(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['pwd' => 'abc'], ['pwd' => 'min:6']);
    }

    public function test_max_string(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['name' => str_repeat('a', 101)], ['name' => 'max:100']);
    }

    public function test_numeric(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['price' => 'abc'], ['price' => 'numeric']);
    }

    public function test_integer(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['qty' => '3.5'], ['qty' => 'integer']);
    }

    public function test_in_enum(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['status' => 'xyz'], ['status' => 'in:pending,paid']);
    }

    public function test_array_rule(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['items' => 'not-array'], ['items' => 'array']);
    }

    public function test_phone_rule(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make(['phone' => 'abc'], ['phone' => 'phone']);
    }

    public function test_multiple_errors_collected(): void
    {
        try {
            Validator::make(
                ['name' => '', 'email' => 'bad'],
                ['name' => 'required', 'email' => 'email']
            );
            $this->fail('Should have thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
        }
    }

    public function test_optional_field_passes_when_null(): void
    {
        $result = Validator::make([], ['phone' => 'phone']);
        $this->assertArrayNotHasKey('phone', $result);
    }
}
