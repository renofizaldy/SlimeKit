<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentContact;

class AdminContentContactService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_contact';

  public function __construct()
  {
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  private function checkExist(array $input)
  {
    $check = ContentContact::where('id', (int) $input['id'])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function list(array $input)
  {
    return ContentContact::all()->toArray();
  }

  public function add(array $input, array $user)
  {
    DB::beginTransaction();
    try {
      //? INSERT TO table
        $insert = ContentContact::create([
          'name'  => $input['name'],
          'value' => $input['value'],
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
        ContentContact::where('id', (int) $input['id'])->update([
          'value' => $input['value']
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

  public function drop(array $input, array $user)
  {
    $check = $this->checkExist($input);
    DB::beginTransaction();
    try {
      //? DELETE table
        ContentContact::where('id', (int) $check->id)->delete();
      //? DELETE table
      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $check->id, 'DELETE');
      //? LOG Record
      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}