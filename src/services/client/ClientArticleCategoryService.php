<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;

use App\Models\ArticleCategory;

class ClientArticleCategoryService
{
  private $cacheKey = 'article_category';
  private $cacheExpired = (60 * 60 * 24); // 24 hours

  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary,
    private Valkey $valkey
  ) {}

  public function list(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? ($input['limit'] ?? 10)));

    //* GENERATE CACHE KEY
      $cacheKey   = sprintf("{$this->cacheKey}:list:site=%s:page=%d:pageSize=%d", $input['site'], $page, $pageSize);
      $cachedData = $this->valkey->get($cacheKey);
      if ($cachedData) {
        $data = json_decode($cachedData, true);
        $data['source'] = 'cache';
        return $data;
      }
    //* GENERATE CACHE KEY

    $query = ArticleCategory::with(['seoMeta:id,id_parent,meta_title,meta_description,meta_robots'])
      ->where('status', 'active')
      ->where('site', $input['site'])
      ->orderBy('id', 'asc');

    $data = $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'id'          => $row->id,
        'site'        => $row->site,
        'slug'        => $row->slug,
        'title'       => $row->title,
        'description' => $row->description,
        'created_at'  => date('Y-m-d H:i:s', strtotime($row->created_at)),
        'updated_at'  => date('Y-m-d H:i:s', strtotime($row->updated_at)),
        'seo_meta'    => [
          'meta_title'       => $row->seoMeta->meta_title ?? '',
          'meta_description' => $row->seoMeta->meta_description ?? '',
          'meta_robots'      => $row->seoMeta->meta_robots ?? '',
        ]
      ]
    );

    //* SAVE TO CACHE
      $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired - 1);
    //* SAVE TO CACHE

    $data['source'] = 'db';
    return $data;
  }

  public function detail(array $input)
  {
    $data  = [];

    //* GENERATE CACHE KEY
      $cacheKey   = sprintf("{$this->cacheKey}:detail:site=%s:slug=%s", $input['site'], $input['slug']);
      $cachedData = $this->valkey->get($cacheKey);
      if ($cachedData) {
        $data = json_decode($cachedData, true);
        $data['source'] = 'cache';
        return $data;
      }
    //* GENERATE CACHE KEY

    $query = ArticleCategory::with(['seoMeta:id,id_parent,meta_title,meta_description,meta_robots'])
      ->where('status', 'active')
      ->where('site', $input['site'])
      ->where('slug', $input['slug'])
      ->first();

    if (!$query) {
      throw new Exception('Not Found', 404);
    }

    $data = [
      'id'          => $query->id,
      'site'        => $query->site,
      'slug'        => $query->slug,
      'title'       => $query->title,
      'description' => $query->description,
      'created_at'  => date('Y-m-d H:i:s', strtotime($query->created_at)),
      'updated_at'  => date('Y-m-d H:i:s', strtotime($query->updated_at)),
      'seo_meta'    => [
        'meta_title'       => $query->seoMeta->meta_title ?? '',
        'meta_description' => $query->seoMeta->meta_description ?? '',
        'meta_robots'      => $query->seoMeta->meta_robots ?? '',
      ]
    ];

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    $data['source'] = 'db'; // Indicate that the data is fetched from the database
    return $data;
  }
}