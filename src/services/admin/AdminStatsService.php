<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Models\Article;
use App\Models\Log;

class AdminStatsService
{
  private $helper;
  private $cloudinary;
  private $tableLog = 'tb_log';

  public function __construct()
  {
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  public function listLog(array $input)
  {
    $query = Log::select([
      $this->tableLog.'.table_name as title',
      $this->tableLog.'.action',
      $this->tableLog.'.created_at as time',
      $this->tableLog.'.id_record',
      DB::raw("COALESCE(tb_user.name, 'Guest') as user")
    ])
      ->leftJoin('tb_user', 'tb_user.id', '=', $this->tableLog.'.id_user')
      ->when(!empty($input['table']), function ($q) use ($input) {
        $q->where($this->tableLog.'.table_name', $input['table']);
      })
      ->when(!empty($input['user']), function ($q) use ($input) {
        $q->where($this->tableLog.'.id_user', $input['user']);
      })
      ->when(!empty($input['action']), function ($q) use ($input) {
        $q->where($this->tableLog.'.action', $input['action']);
      })
      ->orderBy($this->tableLog.'.created_at', 'DESC')
      ->limit($input['limit'])
      ->get()
      ->toArray();

    if (empty($query)) {
      return [];
    }

    $actionMap = [
      'INSERT' => 'create',
      'UPDATE' => 'update',
      'DELETE' => 'drop',
    ];

    $titleMap = [
      'tb_article'          => ['title' => 'Article', 'caption' => 'Article'],
      'tb_article_category' => ['title' => 'Article Category', 'caption' => 'Category'],
      'tb_seo_meta'         => ['title' => 'SEO Meta', 'caption' => 'Meta Tags'],
      'tb_content_faq'      => ['title' => 'Content: FAQ', 'caption' => 'FAQ'],
      'tb_content_gallery'  => ['title' => 'Content: Gallery', 'caption' => 'Image'],
      'tb_content_contact'  => ['title' => 'Content: Contact', 'caption' => 'Contact'],
      'tb_user'             => ['title' => 'Setting: User Account', 'caption' => 'User'],
      'tb_user_role'        => ['title' => 'Setting: User Role', 'caption' => 'User Role'],
    ];

    $contentMap = [
      'tb_article'          => 'title',
      'tb_article_category' => 'title',
      'tb_seo_meta'         => 'meta_title',
      'tb_content_faq'      => 'title',
      'tb_content_gallery'  => 'name',
      'tb_content_contact'  => 'name',
      'tb_user'             => 'name',
      'tb_user_role'        => 'label',
    ];

    $data = array_map(function ($log) use ($actionMap, $titleMap, $contentMap) {

      $content = null;

      if (!empty($log['title']) && isset($contentMap[$log['title']]) && !empty($log['id_record'])) {
        $table  = $log['title'];
        $column = $contentMap[$table];

        try {
          $row = DB::table($table)
            ->select($column)
            ->where('id', $log['id_record'])
            ->first();

          if ($row && isset($row->$column)) {
            $content = $row->$column;
          }
        } catch (\Exception $e) {
          $content = null;
        }
      }

      return [
        'title'   => $titleMap[$log['title']]['title'] ?? $log['title'],
        'caption' => $titleMap[$log['title']]['caption'] ?? null,
        'user'    => $log['user'],
        'action'  => $actionMap[$log['action']] ?? strtolower($log['action']),
        'content' => $content,
        'time'    => $log['time'],
      ];
    }, $query);

    return $data;
  }

  public function totalArticle(array $input)
  {
    $query = Article::where('status', $input['status'])->count();
    $data['total'] = $query;
    return $data;
  }

  public function listCronArticle(array $input)
  {
    $query = Article::with(['cronhooks:id,id_parent,id_cronhooks'])
      ->where('status', 'inactive')
      ->where('publish', '>', date('Y-m-d H:i:s'))
      ->orderBy('publish', 'ASC')
      ->get()->toArray();
    return $query;
  }

  public function dropLog(array $input)
  {
    DB::beginTransaction();
    try {
      //? DELETE table
        Log::where('id', $input['id'])->delete();
      //? DELETE table
      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}