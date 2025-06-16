<?php

namespace App\Validators\Admin;

use Exception;

class AdminContentGalleryValidator
{
  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'add':
        $requiredFields = [
          'title',
          'picture',
          'description'
        ];
        $this->validateRequiredFields($input, $requiredFields);

        $emptyFields = [
          'title',
          'picture'
        ];
        $this->validateEmptyFields($input, $emptyFields);
      break;
      case 'edit':
        $requiredFields = [
          'title',
          'picture',
          'description'
        ];
        $this->validateRequiredFields($input, $requiredFields);

        $emptyFields = [
          'id',
          'title'
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