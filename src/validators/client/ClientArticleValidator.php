<?php

namespace App\Validators\Client;

use Exception;
use App\Helpers\General;

class ClientArticleValidator
{
  private $helper;

  public function __construct()
  {
    $this->helper = new General;
  }

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'list':
        $rules = [
          'order_by'   => 'required|string|not_empty',
          'order_type' => 'required|string|not_empty',
          'limit'      => 'required|integer|not_empty',
          'featured'   => 'required',
          'category'   => 'required',
          'search'     => 'required',
          'page'       => 'required',
        ];
      break;
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