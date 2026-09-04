<?php

namespace App\Validators\Admin;

use Rakit\Validation\Validator;
use App\Helpers\General;
use Exception;

class AdminContentFAQValidator
{
  public function __construct(
    private Validator $validator,
    private General $helper
  ) {}

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'list':
        $rules = $this->helper->paginationRules();
        break;
      case 'add':
        $rules = [
          'title'       => 'required',
          'description' => 'required'
        ];
        break;
      case 'edit':
        $rules = [
          'id'          => 'required|numeric',
          'title'       => 'required',
          'description' => 'required'
        ];
        break;
      case 'sort':
        $rules = [
          'order' => 'required|array'
        ];
        break;
      case 'drop':
        $rules = [
          'id' => 'required|numeric'
        ];
        break;
      default:
        throw new Exception('Can\'t validate some field', 400);
    }

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    if ($validation->fails()) {
      $errors = $validation->errors()->toArray();
      throw new Exception(json_encode(['validation_errors' => $errors]), 400);
    }

    return true;
  }
}