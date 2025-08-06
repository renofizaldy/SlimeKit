<?php

namespace App\Validators\Admin;

use Exception;
use App\Helpers\General;

class AdminStatsValidator
{
  private $helper;

  public function __construct()
  {
    $this->helper = new General;
  }

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'listLog':
        $rules = [
          'limit'  => 'required',
          'table'  => 'required',
          'user'   => 'required',
          'action' => 'required'
        ];
      break;
      case 'dropLog':
        $rules = [
          'id' => 'required|not_empty'
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