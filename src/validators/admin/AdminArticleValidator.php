<?php

namespace App\Validators\Admin;

use Rakit\Validation\Validator;
use App\Helpers\General;
use Exception;

class AdminArticleValidator
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
          'status'   => 'required',
          'site'     => 'required',
          'featured' => 'required',
          'category' => 'required',
          'orderBy'  => 'in:publish,id,title,status,site,author,created_at',
          'orderDir' => 'in:asc,desc,ASC,DESC',
        ]);
        break;
      case 'detail':
        $rules = [
          'id' => 'required|numeric'
        ];
        break;
      case 'add':
        $rules = [
          'title'  => 'required',
          'slug'   => 'required',
          'status' => 'required',
        ];
        if (($input['status'] ?? '') === 'active') {
          $rules = array_merge($rules, [
            'excerpt'          => 'required',
            'content'          => 'required',
            'picture'          => 'required',
            'site'             => 'required',
            'category'         => 'required|numeric',
            'author'           => 'required',
            'publish'          => 'required',
            'meta_title'       => 'required',
            'meta_description' => 'required',
            'meta_robots'      => 'required'
          ]);
        }
        break;
      case 'edit':
        $rules = [
          'title'  => 'required',
          'slug'   => 'required',
          'status' => 'required',
        ];
        if (($input['status'] ?? '') === 'active') {
          $rules = array_merge($rules, [
            'id'               => 'required|numeric',
            'excerpt'          => 'required',
            'content'          => 'required',
            'site'             => 'required',
            'category'         => 'required|numeric',
            'author'           => 'required',
            'publish'          => 'required',
            'meta_title'       => 'required',
            'meta_description' => 'required',
            'meta_robots'      => 'required'
          ]);
        }
        break;
      case 'drop':
        $rules = [
          'id' => 'required|numeric'
        ];
        break;
      case 'status_change':
        $rules = [
          'id'     => 'required|numeric',
          'status' => 'required',
        ];
        break;
      case 'publish_change':
        $rules = [
          'id'      => 'required|numeric',
          'publish' => 'required',
        ];
        break;
      case 'check_slug':
        $rules = [
          'slug' => 'required',
          'site' => 'required'
        ];
        break;
      case 'add_picture':
        $rules = [
          'picture' => 'required'
        ];
        break;
      default:
        throw new Exception('Can\'t validate some field', 400);
    }

    $validation = $this->validator->make($input, $rules);
    $validation->validate();

    if ($validation->fails()) {
      $errors = $validation->errors()->toArray();
      throw new Exception(json_encode(['validation_errors' => $errors]), 400);
    }

    return true;
  }
}