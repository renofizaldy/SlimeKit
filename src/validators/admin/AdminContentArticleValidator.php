<?php

namespace App\Validators\Admin;

use Exception;

class AdminContentArticleValidator
{
  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'list':
        $requiredFields = [
          'status'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      case 'detail':
        $requiredFields = [
          'id'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      case 'add':
        $requiredFields = [
          'picture',
          'status',
          'title',
          'description',
          'author',
          'publish'
        ];
        $this->validateRequiredFields($input, $requiredFields);
        $this->validateEmptyFields($input, $requiredFields);
      break;
      case 'edit':
        $requiredFields = [
          'id',
          'picture',
          'status',
          'title',
          'description',
          'author',
          'publish'
        ];
        $this->validateRequiredFields($input, $requiredFields);

        $emptyFields = [
          'id',
          'status',
          'title',
          'description',
          'author',
          'publish'
        ];
        $this->validateEmptyFields($input, $emptyFields);
      break;
      case 'drop':
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