<?php

namespace App\Validators\Admin;

use Exception;

class AdminSettingUserValidator
{
  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'add':
        $requiredFields = [
          'fullname',
          'username',
          'password',
          'status',
          'role'
        ];
        $this->validateRequiredFields($input, $requiredFields);

        $emptyFields = [
          'fullname',
          'username',
          'password',
          'role'
        ];
        $this->validateEmptyFields($input, $emptyFields);
      break;
      case 'edit':
        $requiredFields = [
          'id',
          'fullname',
          'username',
          'status',
          'role'
        ];
        $this->validateRequiredFields($input, $requiredFields);

        $emptyFields = [
          'id',
          'fullname',
          'username',
          'role'
        ];
        $this->validateEmptyFields($input, $emptyFields);
      break;
      case 'pass':
        $requiredFields = [
          'id',
          'password'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      case 'drop':
        $requiredFields = [
          'id'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      case 'roles_add':
        $requiredFields = [
          'label',
          'role'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      case 'roles_edit':
        $requiredFields = [
          'id',
          'label',
          'role'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      case 'roles_drop':
        $requiredFields = [
          'id'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      default:
        throw new Exception('Can\'t validate some field', 400);
      break;
    }
    return true;
  }

  private function validateRequiredFields(array $input, array $requiredFields)
  {
    foreach ($requiredFields as $field) {
      if (!isset($input[$field])) {
        throw new Exception("Missing required field: {$field}", 400);
      }
    }
  }

  private function validateEmptyFields(array $input, array $requiredFields)
  {
    foreach ($requiredFields as $field) {
      if (empty($input[$field])) {
        throw new Exception("Some field can't be empty: {$field}", 400);
      }
    }
  }
}