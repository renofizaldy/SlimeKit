<?php

namespace App\Validators\Client;

use Exception;
use App\Helpers\General;

class ClientArticleCategoryValidator
{
  private $helper;

  public function __construct()
  {
    $this->helper = new General;
  }

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'detail':
        $rules = [
          'slug' => 'required|string|not_empty',
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