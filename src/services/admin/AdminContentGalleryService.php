<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentGallery;
use App\Models\Picture;

class AdminContentGalleryService
{
  private string $tableMain = 'tb_content_gallery';

  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  private function checkExist(array $input)
  {
    $check = ContentGallery::where('id', (int) $input['id'])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function list(array $input = [])
  {
    $page     = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? 10));

    $query = ContentGallery::with(['picture:id,original,thumbnail'])->orderBy('id', 'desc');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'id'          => $row->id,
        'title'       => $row->name,
        'description' => $row->description,
        'updated_at'  => $row->updated_at,
        'original'    => $row->picture->original ?? null,
        'thumbnail'   => $row->picture->thumbnail ?? null,
      ]
    );
  }

  public function add(array $input, array $user)
  {
    DB::beginTransaction();
    try {
      //? PICTURE UPLOAD
        $picture_id = $this->helper->pictureUpload($this->cloudinary, $input['picture'] ?? null);
      //? PICTURE UPLOAD

      //? INSERT TO table
        $insert = ContentGallery::create([
          'name'        => $input['title'],
          'description' => $input['description'],
          'id_picture'  => $picture_id['id'] ?? null,
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
      //? PICTURE UPLOAD
        $picture_id = $this->helper->pictureUpload($this->cloudinary, $input['picture'] ?? null);
      //? PICTURE UPLOAD

      //? UPDATE ON table
        $updateData = [
          'name'        => $input['title'],
          'description' => $input['description'],
        ];
        if (!empty($picture_id['id'])) {
          $updateData['id_picture'] = $picture_id['id'];
        }
        ContentGallery::where('id', (int) $input['id'])->update($updateData);
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

  public function drop(array $input, array $user)
  {
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      //? DELETE table
        ContentGallery::where('id', (int) $check->id)->delete();
      //? DELETE table

      //? DELETE PICTURE
        Picture::where('id', (int) $check->id_picture)->delete();
      //? DELETE PICTURE

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $check->id, 'DELETE', $this->helper->logRecordChanges($check));
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}