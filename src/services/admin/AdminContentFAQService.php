<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentFAQ;

class AdminContentFAQService
{
  private string $tableMain = 'tb_content_faq';

  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  private function checkExist(array $input)
  {
    $check = ContentFAQ::where('id', (int) $input['id'])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function list(array $input = [])
  {
    $page     = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? 10));

    $query = ContentFAQ::orderBy('sort', 'asc')->orderBy('id', 'asc');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => $row->toArray()
    );
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
        $this->helper->addLog($user, $this->tableMain, $insert->id, 'INSERT', $this->helper->logRecordChanges($insert));
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
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      //? UPDATE ON table
        $updateData = [
          'title'       => $input['title'],
          'description' => $input['description'],
        ];
        ContentFAQ::where('id', (int) $input['id'])->update($updateData);
      //? UPDATE ON table

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE', $this->helper->logUpdateChanges($check, $updateData));
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
        $check = ContentFAQ::where('id', (int) $row['id'])->first();
        //? UPDATE ON table
          $updateData = [
            'sort' => (int) $row['sort'],
          ];
          ContentFAQ::where('id', (int) $row['id'])->update($updateData);
        //? UPDATE ON table

        //? LOG Record
          $this->helper->addLog($user, $this->tableMain, (int) $row['id'], 'UPDATE', $this->helper->logUpdateChanges($check ?? [], $updateData));
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
        $this->helper->addLog($user, $this->tableMain, $check->id, 'DELETE', $this->helper->logRecordChanges($check));
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}