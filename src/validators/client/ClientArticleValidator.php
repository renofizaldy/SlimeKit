<?php

namespace App\Validators\Client;

use Exception;
use Rakit\Validation\Validator;
use App\Helpers\General;

class ClientArticleValidator
{
  public function __construct(
    private Validator $validator,
    private General $helper
  ) {}

  public function validate(string $type, array $input)
  {
    switch ($type) {
      case 'list':
        $rules = $this->helper->paginationRules([
          'site'       => 'required',
          // 'order_by'   => 'required',
          // 'order_type' => 'required',
          // 'limit'      => 'required|integer',
          // 'featured'   => 'required',
          // 'category'   => 'required',
          // 'search'     => 'required',
        ]);
        break;
      case 'similar':
        $rules = [
          'site'       => 'required',
          // 'order_by'   => 'required',
          // 'order_type' => 'required',
          // 'limit'      => 'required|integer',
          // 'slug'       => 'required',
        ];
        break;
      case 'detail':
        $rules = [
          'site' => 'required',
          'slug' => 'required',
        ];
        break;
      case 'publish':
        $rules = [
          'site' => 'required',
          'slug' => 'required',
        ];
        break;
      default:
        throw new Exception('Can\'t validate some field', 400);
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