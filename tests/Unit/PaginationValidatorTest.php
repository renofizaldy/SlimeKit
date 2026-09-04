<?php

namespace Tests\Unit;

use Tests\TestCase;
use Rakit\Validation\Validator;
use App\Helpers\General;
use App\Lib\Valkey;
use Exception;

class SamplePaginationValidator
{
  public function __construct(
    private Validator $validator,
    private General $helper
  ) {}

  public function validate(string $type, array $input): bool
  {
    switch ($type) {
      case 'list':
        $rules = $this->helper->paginationRules([
          'status'   => 'required',
          'site'     => 'required',
          'featured' => 'required',
          'category' => 'required',
        ]);
        break;
      default:
        throw new Exception("Can't validate some field", 400);
    }

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    if ($validation->fails()) {
      throw new Exception(json_encode($validation->errors()->firstOfAll()), 400);
    }

    return true;
  }
}

class PaginationValidatorTest extends TestCase
{
  private Validator $validator;
  private General $helper;

  protected function setUp(): void
  {
    parent::setUp();
    $this->validator = new Validator();

    $mockValkey = $this->createMock(Valkey::class);
    $this->helper = new General($mockValkey);
  }

  //* =================== 1. General->paginationRules ===================

  public function test_pagination_rules_defaults()
  {
    $rules = $this->helper->paginationRules();

    $this->assertEquals([
      'page'     => 'numeric|min:1',
      'pageSize' => 'numeric|min:1',
    ], $rules);
  }

  public function test_pagination_rules_merge_with_custom_rules()
  {
    $custom = [
      'status' => 'required',
      'site'   => 'required',
    ];

    $rules = $this->helper->paginationRules($custom);

    $this->assertEquals([
      'status'   => 'required',
      'site'     => 'required',
      'page'     => 'numeric|min:1',
      'pageSize' => 'numeric|min:1',
    ], $rules);
  }

  //* =================== 2. Rakit Validation Engine with Pagination Rules ===================

  public function test_validation_passes_when_pagination_params_omitted()
  {
    $rules = $this->helper->paginationRules(['site' => 'required']);
    $input = ['site' => '1'];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertFalse($validation->fails());
  }

  public function test_validation_passes_with_valid_pagination_params()
  {
    $rules = $this->helper->paginationRules(['site' => 'required']);
    $input = [
      'site'     => '1',
      'page'     => 2,
      'pageSize' => 20,
    ];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertFalse($validation->fails());
  }

  public function test_validation_passes_with_numeric_string_pagination_params()
  {
    $rules = $this->helper->paginationRules(['site' => 'required']);
    $input = [
      'site'     => '1',
      'page'     => '3',
      'pageSize' => '15',
    ];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertFalse($validation->fails());
  }

  public function test_validation_fails_with_negative_page()
  {
    $rules = $this->helper->paginationRules();
    $input = ['page' => -1];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertTrue($validation->fails());
    $errors = $validation->errors()->toArray();
    $this->assertArrayHasKey('page', $errors);
  }

  public function test_validation_fails_with_zero_page()
  {
    $rules = $this->helper->paginationRules();
    $input = ['page' => 0];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertTrue($validation->fails());
    $errors = $validation->errors()->toArray();
    $this->assertArrayHasKey('page', $errors);
  }

  public function test_validation_fails_with_non_numeric_page()
  {
    $rules = $this->helper->paginationRules();
    $input = ['page' => 'abc'];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertTrue($validation->fails());
    $errors = $validation->errors()->toArray();
    $this->assertArrayHasKey('page', $errors);
  }

  public function test_validation_fails_with_negative_page_size()
  {
    $rules = $this->helper->paginationRules();
    $input = ['pageSize' => -5];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertTrue($validation->fails());
    $errors = $validation->errors()->toArray();
    $this->assertArrayHasKey('pageSize', $errors);
  }

  public function test_validation_fails_with_zero_page_size()
  {
    $rules = $this->helper->paginationRules();
    $input = ['pageSize' => 0];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertTrue($validation->fails());
    $errors = $validation->errors()->toArray();
    $this->assertArrayHasKey('pageSize', $errors);
  }

  public function test_validation_fails_with_non_numeric_page_size()
  {
    $rules = $this->helper->paginationRules();
    $input = ['pageSize' => 'xyz'];

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    $this->assertTrue($validation->fails());
    $errors = $validation->errors()->toArray();
    $this->assertArrayHasKey('pageSize', $errors);
  }

  //* =================== 3. Module Validators Integration Pattern ===================

  public function test_validator_list_pagination()
  {
    $validator = new SamplePaginationValidator($this->validator, $this->helper);

    // Valid without pagination
    $validInput = [
      'status'   => 'active',
      'site'     => '1',
      'featured' => 'all',
      'category' => 'all',
    ];
    $this->assertTrue($validator->validate('list', $validInput));

    // Valid with pagination
    $validWithPagination = array_merge($validInput, ['page' => 2, 'pageSize' => 15]);
    $this->assertTrue($validator->validate('list', $validWithPagination));

    // Invalid negative page
    $this->expectException(Exception::class);
    $this->expectExceptionCode(400);
    $validator->validate('list', array_merge($validInput, ['page' => -1]));
  }

  public function test_validator_list_pagination_invalid_page_size()
  {
    $validator = new SamplePaginationValidator($this->validator, $this->helper);

    $validInput = [
      'status'   => 'active',
      'site'     => '1',
      'featured' => 'all',
      'category' => 'all',
    ];

    $this->expectException(Exception::class);
    $this->expectExceptionCode(400);
    $validator->validate('list', array_merge($validInput, ['pageSize' => 'invalid']));
  }
}
