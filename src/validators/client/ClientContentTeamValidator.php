<?php

namespace App\Validators\Client;

use Exception;

class ClientContentTeamValidator
{
  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'list':
        $requiredFields = [
          'limit',
        ];
        $this->validateRequiredFields($input, $requiredFields);
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