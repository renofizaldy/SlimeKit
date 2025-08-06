<?php

namespace App\Validators\Admin;

use Exception;
use App\Helpers\General;

class AdminAuthValidator
{
  private $helper;

  public function __construct()
  {
    $this->helper = new General;
  }

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'login':
        $rules = [
          'username' => 'required|not_empty',
          'password' => 'required|not_empty'
        ];
      break;
      default:
        throw new Exception('Can\'t validate some field', 400);
      break;
    }
    $this->helper->validateByRules($input, $rules);
    return true;
  }
}