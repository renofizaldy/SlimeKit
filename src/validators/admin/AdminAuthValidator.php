<?php

namespace App\Validators\Admin;

use Rakit\Validation\Validator;
use Exception;

class AdminAuthValidator
{
  public function __construct(private Validator $validator) {}

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'login':
        $rules = [
          'username' => 'required',
          'password' => 'required'
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