<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentFAQ;

class AdminContentFAQService
{
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_faq';

  public function __construct()
  {
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  private function checkExist(array $input)
  {
    $check = ContentFAQ::where('id', (int) $input['id'])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function list(array $input)
  {
    return ContentFAQ::all()->toArray();
  }

  public function add(array $input, array $user)
  {
    DB::beginTransaction();
    try {
      //? INSERT TO table
        $insert = ContentFAQ::create([
          'title'       => $input['title'],
          'description' => $input['description'],
        ]);
      //? INSERT TO table

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $insert->id, 'INSERT');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    DB::beginTransaction();
    try {
      //? UPDATE ON table
        ContentFAQ::where('id', (int) $input['id'])->update([
          'title'       => $input['title'],
          'description' => $input['description'],
        ]);
      //? UPDATE ON table

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function sort(array $input, array $user)
  {
    DB::beginTransaction();
    try {
      foreach($input['order'] as $row) {
        //? UPDATE ON table
          ContentFAQ::where('id', (int) $row['id'])->update([
            'sort' => (int) $row['sort'],
          ]);
        //? UPDATE ON table

        //? LOG Record
          $this->helper->addLog($user, $this->tableMain, (int) $row['id'], 'UPDATE');
        //? LOG Record
      }

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function drop(array $input, array $user)
  {
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      //? DELETE table
        ContentFAQ::where('id', $check->id)->delete();
      //? DELETE table

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $check->id, 'DELETE');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}