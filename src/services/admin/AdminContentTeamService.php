<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentTeam;
use App\Models\Picture;

class AdminContentTeamService
{
  private string $tableMain = 'tb_content_team';

  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  private function checkExist(array $input)
  {
    $check = ContentTeam::where('id', (int) $input['id'])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function list(array $input = [])
  {
    $page     = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? 10));

    $query = ContentTeam::with(['picture:id,original,thumbnail'])->orderBy('id', 'desc');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'id'          => $row->id,
        'name'        => $row->name,
        'title'       => $row->title,
        'link'        => $row->link,
        'updated_at'  => $row->updated_at,
        'original'    => $row->picture->original ?? null,
        'large'       => $this->helper->formatCloudinaryUrl($row->picture->original ?? $row->picture->thumbnail ?? null, 'c_fill,w_1200,h_675,g_auto,q_auto,f_auto'),
        'thumbnail'   => $row->picture->thumbnail ?? null,
      ]
    );
  }

  public function add(array $input, array $user)
  {
    //? PICTURE UPLOAD OUTSIDE TRANSACTION
    $uploadedPicture = !empty($input['picture']) ? $this->helper->uploadImage($this->cloudinary, $input['picture']) : null;

    DB::beginTransaction();
    try {
      //? PICTURE SAVE
        $picture    = $uploadedPicture ? $this->helper->savePicture($uploadedPicture) : null;
        $picture_id = $picture ? ['id' => $picture->id, 'url' => $uploadedPicture['original']] : null;
      //? PICTURE SAVE

      //? INSERT TO table
        $insert = ContentTeam::create([
          'name'        => $input['name'],
          'title'       => $input['title'],
          'link'        => $input['link'] ?? null,
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
      if ($uploadedPicture && !empty($uploadedPicture['public_id'])) {
        try {
          $this->cloudinary->delete($uploadedPicture['public_id']);
        } catch (\Throwable) {}
      }
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    $check = $this->checkExist($input);

    //? PICTURE UPLOAD OUTSIDE TRANSACTION
    $uploadedPicture = !empty($input['picture']) ? $this->helper->uploadImage($this->cloudinary, $input['picture']) : null;

    DB::beginTransaction();
    try {
      //? PICTURE SAVE
        $picture    = $uploadedPicture ? $this->helper->savePicture($uploadedPicture) : null;
        $picture_id = $picture ? ['id' => $picture->id, 'url' => $uploadedPicture['original']] : null;
      //? PICTURE SAVE

      //? UPDATE ON table
        $updateData = [
          'name'  => $input['name'],
          'title' => $input['title'],
        ];
        if (array_key_exists('link', $input)) {
          $updateData['link'] = $input['link'];
        }
        if (!empty($picture_id['id'])) {
          $updateData['id_picture'] = $picture_id['id'];
        }
        ContentTeam::where('id', (int) $input['id'])->update($updateData);
      //? UPDATE ON table

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE', $this->helper->logUpdateChanges($check, $updateData));
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      if ($uploadedPicture && !empty($uploadedPicture['public_id'])) {
        try {
          $this->cloudinary->delete($uploadedPicture['public_id']);
        } catch (\Throwable) {}
      }
      throw $e;
    }
  }

  public function drop(array $input, array $user)
  {
    $check = $this->checkExist($input);
    $pictureCloudId = null;

    DB::beginTransaction();
    try {
      //? DELETE table
        ContentTeam::where('id', (int) $check->id)->delete();
      //? DELETE table

      //? DELETE PICTURE
        if (!empty($check->id_picture)) {
          $picture = Picture::find($check->id_picture);
          if ($picture) {
            $pictureCloudId = $picture->id_cloud ?? null;
            $picture->delete();
          }
        }
      //? DELETE PICTURE

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $check->id, 'DELETE', $this->helper->logRecordChanges($check));
      //? LOG Record

      DB::commit();

      //! DROP PICTURE ON CLOUD (AFTER COMMIT)
      if (!empty($pictureCloudId)) {
        try {
          $this->cloudinary->delete($pictureCloudId);
        } catch (\Throwable $e) {
          error_log('Failed to delete Cloudinary picture after drop: ' . $e->getMessage());
        }
      }
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}