<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;
use App\Lib\Cronhooks;

use App\Models\Article;

class ClientArticleService
{
  private $helper;
  private $cloudinary;
  private $valkey;
  private $cronhooks;
  private $cacheKey = 'article';
  private $cacheExpired = (60 * 60 * 24); // 24 hours

  public function __construct()
  {
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
    $this->valkey = new Valkey;
    $this->cronhooks = new Cronhooks;
  }

  private function checkExist(array $input)
  {
    $check = Article::where('slug', $input['slug'])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function list(array $input)
  {
    //* INIT
      $page   = max(1, (int) ($input['page'] ?? 1));   //? default page 1
      $limit  = max(1, (int) ($input['limit'] ?? 10)); //? default 10 per page
      $offset = ($page - 1) * $limit;
      $search = $input['search'] ? $this->helper->filterSearchQuery($input['search']) : '';
    //* INIT

    //* GENERATE CACHE KEY
      $cacheKey   = sprintf(
        "{$this->cacheKey}:list:order_by=%s:order_type=%s:limit=%d:page=%d:search=%s:category=%s:featured=%s",
        $input['order_by'] ?? 'publish',
        $input['order_type'] ?? 'DESC',
        $limit,
        $page,
        $search,
        $input['category'] ?? '',
        $input['featured'] ?? ''
      );
      $cachedData = $this->valkey->get($cacheKey);
      if ($cachedData) {
        $data = json_decode($cachedData, true);
        $data['source'] = 'cache';
        return $data;
      }
    //* GENERATE CACHE KEY

    //* NEW
      $baseQuery = Article::where('status', 'active');

      //? FEATURED
        if (!empty($input['featured'])) {
          $baseQuery->whereRaw('? = ANY(featured)', [$input['featured']]);
        }
      //? FEATURED
      //? CATEGORY
        if (!empty($input['category'])) {
          $baseQuery->whereHas('category', function ($q) use ($input) {
            $q->where('slug', $input['category'])
              ->where('status', 'active');
          });
        }
      //? CATEGORY
      //? SEARCH
        if (!empty($search)) {
          $baseQuery->where(function ($q) use ($search) {
            $q->whereRaw('LOWER(title) LIKE ?', ['%' . $search . '%'])
              ->orWhereRaw('LOWER(excerpt) LIKE ?', ['%' . $search . '%']);
          });
        }
      //? SEARCH

      //* TOTAL COUNT
        $totalCount = (clone $baseQuery)->count();
      //* TOTAL COUNT

      //* FORMAT RESULT
        $data = [
          'totalPages'  => (int) ceil($totalCount / $limit),
          'currentPage' => $page,
          'search'      => $search,
          'category'    => $input['category'] ?? '',
          'featured'    => $input['featured'] ?? '',
          'list'        => []
        ];
      //* FORMAT RESULT

      //* ORDER BY
        if (!empty($input['order_by']) && !empty($input['order_type'])) {
          $allowedOrderBy = ['id', 'status', 'slug', 'title', 'content', 'excerpt', 'author', 'publish', 'featured', 'read_time', 'created_at', 'updated_at'];

          if (!in_array($input['order_by'], $allowedOrderBy)) {
            throw new Exception('Invalid order by field.', 400);
          }
          if (!in_array(strtolower($input['order_type']), ['asc', 'desc'])) {
            throw new Exception('Invalid order type.', 400);
          }

          $baseQuery->orderBy($input['order_by'], $input['order_type']);
        } else {
          $baseQuery->orderBy('publish', 'DESC');
        }
      //* ORDER BY

      //* MAIN QUERY
        $rows = $baseQuery
          ->with([
            'picture:id,original,thumbnail,caption',
            'seoMeta:id,id_parent,seo_keyphrase,meta_title,meta_description,meta_robots',
            'category:id,slug,title,description'
          ])
          ->skip($offset)
          ->take($limit)
          ->get();
      //* MAIN QUERY

      //* RESULT LOOP
        foreach ($rows as $row) {
          $data['list'][] = [
            'slug'        => $row->slug,
            'title'       => $row->title,
            'description' => strip_tags($row->excerpt),
            'author'      => $row->author,
            'read_time'   => $row->read_time ?? 0,
            'publish'     => date('Y-m-d H:i:s', strtotime($row->publish)),
            'featured'    => $row->featured ?? [],
            'picture'     => [
              'original'  => $row->picture->original ?? null,
              'thumbnail' => $row->picture->thumbnail ?? null,
              'caption'   => $row->picture->caption ?? '',
            ],
            'category'    => [
              'slug'        => $row->category->slug ?? '',
              'title'       => $row->category->title ?? '',
              'description' => $row->category->description ?? '',
            ],
            'seo_meta'    => [
              'keyphrase'        => $row->seoMeta->seo_keyphrase ?? '',
              'meta_title'       => $row->seoMeta->meta_title ?? '',
              'meta_description' => $row->seoMeta->meta_description ?? '',
              'meta_robots'      => $row->seoMeta->meta_robots ?? '',
            ]
          ];
        }
      //* RESULT LOOP

      //* SAVE TO CACHE
        $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired - 1);
      //* SAVE TO CACHE
    //* NEW

    $data['source'] = 'db';
    return $data;
  }

  public function detail(array $input)
  {
    $data = [
      'detail' => [],
    ];

    //* GENERATE CACHE KEY
      $cacheKey   = sprintf(
        "{$this->cacheKey}:detail:slug=%s",
        $input['slug']
      );
      $cachedData = $this->valkey->get($cacheKey);
      if ($cachedData) {
        $data = json_decode($cachedData, true);
        $data['source'] = 'cache';
        return $data;
      }
    //* GENERATE CACHE KEY

    $query = Article::with([
      'picture:id,original,thumbnail,caption',
      'seoMeta:id,id_parent,seo_keyphrase,meta_title,meta_description,meta_robots',
      'category' => fn($q) =>
        $q->select('id', 'slug', 'title', 'description')
          ->where('status', 'active')
    ])->where([
      'status' => 'active',
      'slug'   => $input['slug']
    ])->first();

    if (!$query) {
      throw new Exception('Not Found', 404);
    }

    $data['detail'] = [
      'slug'      => $query->slug,
      'title'     => $query->title,
      'excerpt'   => $query->excerpt,
      'content'   => $query->content,
      'author'    => $query->author,
      'read_time' => $query->read_time ?? 0,
      'publish'   => date('Y-m-d H:i:s', strtotime($query->publish)),
      'updated'   => date('Y-m-d H:i:s', strtotime($query->updated_at)),
      'picture'   => [
        'original'  => $query->picture->original ?? null,
        'thumbnail' => $query->picture->thumbnail ?? null,
        'caption'   => $query->picture->caption ?? '',
      ],
      'category'  => [
        'slug'        => $query->category->slug ?? '',
        'title'       => $query->category->title ?? '',
        'description' => $query->category->description ?? '',
      ],
      'seo_meta'  => [
        'keyphrase'        => $query->seoMeta->seo_keyphrase ?? '',
        'meta_title'       => $query->seoMeta->meta_title ?? '',
        'meta_description' => $query->seoMeta->meta_description ?? '',
        'meta_robots'      => $query->seoMeta->meta_robots ?? '',
      ]
    ];

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    $data['source'] = 'db';
    return $data;
  }

  public function similar(array $input)
  {
    //* INIT
    $limit  = max(1, (int) ($input['limit'] ?? 10)); //? default 10 per page

    //* GENERATE CACHE KEY
      $cacheKey   = sprintf(
        "{$this->cacheKey}:similar:slug=%s:order_by=%s:order_type=%s:limit=%d",
        $input['slug'],
        $input['order_by'] ?? 'publish',
        $input['order_type'] ?? 'DESC',
        $limit,
      );
      $cachedData = $this->valkey->get($cacheKey);
      if ($cachedData) {
        $data = json_decode($cachedData, true);
        $data['source'] = 'cache';
        return $data;
      }
    //* GENERATE CACHE KEY

    //* GET TITLE
      $article = Article::where('slug', $input['slug'])
        ->where('status', 'active')
        ->first();
      if (!$article || empty($article->title)) {
        throw new Exception('Not Found', 404);
      }
      $title = $article->title;

    //* FORMAT RESULT
    $data = [
      'list' => []
    ];

    //* BASE QUERY
      $baseQuery = Article::where('status', 'active')
        ->where('slug', '!=', $input['slug'])
        ->whereRaw("similarity(title, ?) > 0.2", [$title]);
    //* BASE QUERY

    //* ORDER BY
      if (!empty($input['order_by']) && !empty($input['order_type'])) {
        $allowedOrderBy = ['id', 'status', 'slug', 'title', 'content', 'excerpt', 'author', 'publish', 'featured', 'read_time', 'created_at', 'updated_at'];

        if (!in_array($input['order_by'], $allowedOrderBy)) {
          throw new Exception('Invalid order by field.', 400);
        }
        if (!in_array(strtolower($input['order_type']), ['asc', 'desc'])) {
          throw new Exception('Invalid order type.', 400);
        }

        $baseQuery->orderBy($input['order_by'], $input['order_type']);
      } else {
        $baseQuery->orderBy('publish', 'DESC');
      }
    //* ORDER BY

    //* MAIN QUERY
      $rows = $baseQuery
        ->with([
          'picture:id,original,thumbnail,caption',
          'seoMeta:id,id_parent,seo_keyphrase,meta_title,meta_description,meta_robots',
          'category' => function ($q) use ($input) {
            $q->select('id', 'slug', 'title', 'description')
              ->where('status', 'active');
          }
        ])
        ->limit($limit)
        ->get();
    //* MAIN QUERY

    //* RESULT LOOP
      foreach ($rows as $row) {
        $data['list'][] = [
          'slug'        => $row->slug,
          'title'       => $row->title,
          'description' => strip_tags($row->excerpt),
          'author'      => $row->author,
          'read_time'   => $row->read_time ?? 0,
          'publish'     => date('Y-m-d H:i:s', strtotime($row->publish)),
          'featured'    => $row->featured ?? [],
          'picture'     => [
            'original'  => $row->picture->original ?? null,
            'thumbnail' => $row->picture->thumbnail ?? null,
            'caption'   => $row->picture->caption ?? '',
          ],
          'category'    => [
            'slug'        => $row->category->slug ?? '',
            'title'       => $row->category->title ?? '',
            'description' => $row->category->description ?? '',
          ],
          'seo_meta'    => [
            'keyphrase'        => $row->seoMeta->seo_keyphrase ?? '',
            'meta_title'       => $row->seoMeta->meta_title ?? '',
            'meta_description' => $row->seoMeta->meta_description ?? '',
            'meta_robots'      => $row->seoMeta->meta_robots ?? '',
          ]
        ];
      }
    //* RESULT LOOP

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    $data['source'] = 'db';
    return $data;
  }

  public function publish(array $input, array $user)
  {
    //? CHECK ARTICLE
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      //? UPDATE STATUS
        Article::where('id', $check->id)
          ->update([
            'status' => 'active',
          ]);
      //? UPDATE STATUS

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $check->id, 'UPDATE');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:admin"));
      //? DELETE CACHE

      DB::commit();

      //? CHANGE CRONJOB
        $this->helper->recomputeCron($this->cronhooks);
      //? CHANGE CRONJOB
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}