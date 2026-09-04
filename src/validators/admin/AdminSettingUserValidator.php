<?php

namespace App\Validators\Admin;

use Exception;
use Rakit\Validation\Validator;
use App\Helpers\General;

class AdminSettingUserValidator
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
          'fullname' => 'required',
          'username' => 'required',
          'password' => 'required',
          'status'   => 'required',
          'role'     => 'required'
        ];
        break;
      case 'edit':
        $rules = [
          'id'       => 'required',
          'fullname' => 'required',
          'username' => 'required',
          'status'   => 'required',
          'role'     => 'required'
        ];
        break;
      case 'pass':
        $rules = [
          'id'       => 'required',
          'password' => 'required'
        ];
        break;
      case 'drop':
        $rules = [
          'id' => 'required'
        ];
        break;
      case 'roles_add':
        $rules = [
          'label' => 'required',
          'role'  => 'required'
        ];
        break;
      case 'roles_edit':
        $rules = [
          'id'    => 'required',
          'label' => 'required',
          'role'  => 'required'
        ];
        break;
      case 'roles_drop':
        $rules = [
          'id' => 'required'
        ];
        break;
      default:
        throw new Exception('Unknown validation type', 400);
    }

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    if ($validation->fails()) {
      $errors = $validation->errors()->firstOfAll();
      throw new Exception(json_encode(['validation_errors' => $errors]), 400);
    }

    return true;
  }
}