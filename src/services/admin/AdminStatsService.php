<?php

namespace App\Services\Admin;

use Exception;
use DatePeriod;
use DateTime;
use DateInterval;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Models\Article;
use App\Models\Log;
use App\Models\ArticleStats;

class AdminStatsService
{
  private $tableLog = 'tb_log';

  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  public function listLog(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? ($input['limit'] ?? 10)));

    $query = Log::select([
      $this->tableLog.'.table_name as title',
      $this->tableLog.'.action',
      $this->tableLog.'.created_at as time',
      $this->tableLog.'.id_record',
      $this->tableLog.'.changes',
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
      ->orderBy($this->tableLog.'.id', 'DESC');

    $actionMap = [
      'INSERT' => 'create',
      'UPDATE' => 'update',
      'DELETE' => 'drop',
    ];

    $titleMap = [
      'tb_article'               => ['title' => 'Article', 'caption' => 'Article'],
      'tb_article_category'      => ['title' => 'Article Category', 'caption' => 'Category'],
      'tb_seo_meta'              => ['title' => 'SEO Meta', 'caption' => 'Meta Tags'],
      'tb_content_faq'           => ['title' => 'Content: FAQ', 'caption' => 'FAQ'],
      'tb_content_gallery'       => ['title' => 'Content: Gallery', 'caption' => 'Image'],
      'tb_content_contact'       => ['title' => 'Content: Contact', 'caption' => 'Contact'],
      'tb_content_team'          => ['title' => 'Content: Team', 'caption' => 'Team'],
      'tb_user'                  => ['title' => 'Setting: User Account', 'caption' => 'User'],
      'tb_user_role'             => ['title' => 'Setting: User Role', 'caption' => 'User Role'],
    ];

    $contentMap = [
      'tb_article'               => 'title',
      'tb_article_category'      => 'title',
      'tb_seo_meta'              => 'meta_title',
      'tb_content_faq'           => 'title',
      'tb_content_gallery'       => 'name',
      'tb_content_contact'       => 'name',
      'tb_content_team'          => 'name',
      'tb_user'                  => 'name',
      'tb_user_role'             => 'label',
    ];

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      function ($log) use ($actionMap, $titleMap, $contentMap) {
        $content = null;
        $changes = is_array($log->changes) ? $log->changes : json_decode($log->changes ?? '[]', true);

        if (!empty($log->title) && isset($contentMap[$log->title]) && !empty($log->id_record)) {
          $table  = $log->title;
          $column = $contentMap[$table];

          try {
            $row = DB::table($table)
              ->select($column)
              ->where('id', $log->id_record)
              ->first();

            if ($row && isset($row->$column)) {
              $content = $row->$column;
            }
          } catch (\Exception $e) {
            $content = null;
          }

          if ($content === null && is_array($changes)) {
            $content = $changes['record'][$column]
              ?? $changes['after'][$column]
              ?? $changes['before'][$column]
              ?? null;
          }
        }

        return [
          'title'   => $titleMap[$log->title]['title'] ?? $log->title,
          'caption' => $titleMap[$log->title]['caption'] ?? null,
          'user'    => $log->user,
          'action'  => $actionMap[$log->action] ?? strtolower($log->action),
          'content' => $content,
          'time'    => $log->time,
        ];
      }
    );
  }

  public function totalArticle(array $input)
  {
    $query = Article::where('status', $input['status'])->count();
    $data['total'] = $query;
    return $data;
  }

  public function listCronArticle(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? 10));

    $query = Article::with(['cronhooks:id,id_parent,id_cronhooks'])
      ->where('status', 'inactive')
      ->where('publish', '>', date('Y-m-d H:i:s'))
      ->orderBy('publish', 'ASC');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'id'           => $row->id,
        'id_cronhooks' => (!is_null($row->cronhooks)) ? $row->cronhooks->id_cronhooks : null,
        'status'       => $row->status,
        'title'        => $row->title,
        'publish'      => $row->publish,
      ]
    );
  }

  public function dropLog(array $input)
  {
    DB::beginTransaction();
    try {
      //? DELETE table
        Log::where('id', $input['id'])->delete();
      //? DELETE table
      DB::commit();
      return true;
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function listArticleVisits(array $input)
  {
    $startDate = $input['start'];
    $endDate   = $input['end'];

    // 1. ZERO FILLING: Buat array dasar dengan nilai 0 untuk setiap hari dalam rentang waktu
    $chartData = [];
    $period = new DatePeriod(
      new DateTime($startDate),
      new DateInterval('P1D'),
      (new DateTime($endDate))->modify('+1 day') // +1 agar end_date ikut masuk dalam iterasi
    );

    foreach ($period as $dt) {
      $dateStr = $dt->format('Y-m-d');
      $chartData[$dateStr] = [
        'date'   => $dateStr,
        'visits' => 0
      ];
    }

    // 2. QUERY DATABASE: Ambil data dari PostgreSQL yang sudah di Group By Date
    $query = ArticleStats::select([
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(id) as visits')
      ])
      ->whereDate('created_at', '>=', $startDate)
      ->whereDate('created_at', '<=', $endDate)
      ->where('country_code', 'ID')
      ->groupBy(DB::raw('DATE(created_at)'))
      ->orderBy('date', 'ASC')
      ->get();

    // 3. MAPPING DATA: Timpa nilai 0 di $chartData dengan hasil real dari database
    foreach ($query as $row) {
      if (isset($chartData[$row->date])) {
        $chartData[$row->date]['visits'] = (int) $row->visits;
      }
    }

    // 4. RETURN: Reset key array agar menjadi format Array List [{}, {}, {}] saat diubah ke JSON
    return array_values($chartData);
  }

  public function listArticleVisitsBySlug(array $input)
  {
    $startDate = $input['start'];
    $endDate   = $input['end'];

    $query = ArticleStats::select([
        'slug',
        DB::raw('COUNT(id) as visits')
      ])
      ->whereDate('created_at', '>=', $startDate)
      ->whereDate('created_at', '<=', $endDate)
      ->where('country_code', 'ID')
      ->groupBy('slug')
      ->orderBy('visits', 'DESC')
      ->get();
    $slugs = $query->pluck('slug')->toArray();

    $titles = [];
    if (!empty($slugs)) {
      $titles = Article::whereIn('slug', $slugs)
        ->pluck('title', 'slug') 
        ->toArray();
    }

    $data = [];
    foreach ($query as $row) {
      $data[] = [
        'slug'   => $row->slug,
        'title'  => $titles[$row->slug] ?? 'Tanpa Judul / Artikel Dihapus',
        'visits' => (int) $row->visits
      ];
    }

    return $data;
  }
}