<?php

namespace App\Validators\Admin;

use Exception;
use App\Helpers\General;

class AdminArticleValidator
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
          'status'   => 'required|string|not_empty',
          'featured' => 'required|string|not_empty',
          'category' => 'required',
        ];
      break;
      case 'detail':
        $rules = [
          'id' => 'required|integer'
        ];
      break;
      case 'add':
        $rules = [
          'title'  => 'required|string|not_empty',
          'slug'   => 'required|string|not_empty',
          'status' => 'required|not_empty',
        ];
        if (($input['status'] ?? '') === 'active') {
          $rules = array_merge($rules, [
            'excerpt'          => 'required|string|not_empty',
            'content'          => 'required|string|not_empty',
            'picture'          => 'required|not_empty',
            'category'         => 'required|integer',
            'author'           => 'required|string|not_empty',
            'publish'          => 'required|date',
            'meta_title'       => 'required|string|not_empty',
            'meta_description' => 'required|string|not_empty',
            'meta_robots'      => 'required|string|not_empty'
          ]);
        }
      break;
      case 'edit':
        $rules = [
          'title'  => 'required|string|not_empty',
          'slug'   => 'required|string|not_empty',
          'status' => 'required|not_empty',
        ];
        if (($input['status'] ?? '') === 'active') {
          $rules = array_merge($rules, [
            'id'               => 'required|integer',
            'excerpt'          => 'required|string|not_empty',
            'content'          => 'required|string|not_empty',
            'category'         => 'required|integer',
            'author'           => 'required|string|not_empty',
            'publish'          => 'required|date',
            'meta_title'       => 'required|string|not_empty',
            'meta_description' => 'required|string|not_empty',
            'meta_robots'      => 'required|string|not_empty'
          ]);
        }
      break;
      case 'drop':
        $rules = [
          'id' => 'required|integer|not_empty'
        ];
      break;
      case 'check_slug':
        $rules = [
          'slug' => 'required|string|not_empty',
        ];
      break;
      case 'add_picture':
        $rules = [
          'picture' => 'required|not_empty'
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