<?php

namespace App\Validators\Admin;

use Exception;

class AdminStatsValidator
{
  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'listLog':
        $requiredFields = [
          'limit',
          'table',
          'user',
          'action'
        ];
        $this->validateRequiredFields($input, $requiredFields);
      break;
      case 'dropLog':
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
        throw new Exception('Missing required field', 400);
      }
    }
  }

  private function validateEmptyFields(array $input, array $requiredFields)
  {
    foreach ($requiredFields as $field) {
      if (empty($input[$field])) {
        throw new Exception('Some field can\'t be empty', 400);
      }
    }
  }
}