<?php

namespace App\Services\Client;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;

class ClientArticleCategoryService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $valkey;
  private $tableMain = 'tb_article_category';
  private $tableSeoMeta = 'tb_seo_meta';
  private $cacheKey = 'article_category';
  private $cacheExpired = (60 * 60 * 24); // 24 hours

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
    $this->valkey = new Valkey;
  }

  public function list(array $input)
  {
    $data = [
      'list' => [],
    ];

    //* GENERATE CACHE KEY
    $cacheKey   = sprintf("{$this->cacheKey}:list:site=%s");
    $cachedData = $this->valkey->get($cacheKey);
    if ($cachedData) {
      $data = json_decode($cachedData, true);
      $data['source'] = 'cache'; // Indicate that the data is fetched from cache
      return $data;
    }

    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tableSeoMeta,
        $this->tableSeoMeta,
        $this->tableMain.'.id = '.$this->tableSeoMeta.'.id_parent AND '.$this->tableSeoMeta.'.type = :type'
      )
      ->where('status = :status')
      ->setParameter('status', 'active')
      ->setParameter('type', 'article_category')
      ->executeQuery()
      ->fetchAllAssociative();

    if (!empty($query)) {
      foreach ($query as $row) {
        $data['list'][] = [
          'id'          => $row['id'],
          'slug'        => $row['slug'],
          'title'       => $row['title'],
          'description' => $row['description'],
          'created_at'  => date('Y-m-d H:i:s', strtotime($row['created_at'])),
          'updated_at'  => date('Y-m-d H:i:s', strtotime($row['updated_at'])),
          'seo_meta'    => [
            'meta_title'       => $row['meta_title'] ?? '',
            'meta_description' => $row['meta_description'] ?? '',
            'meta_robots'      => $row['meta_robots'] ?? '',
          ]
        ];
      }
    }

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    $data['source'] = 'db'; // Indicate that the data is fetched from the database
    return $data;
  }

  public function detail(array $input)
  {
    $data  = [];

    //* GENERATE CACHE KEY
    $cacheKey   = sprintf("{$this->cacheKey}:detail:site=%s:slug=%s", $input['slug']);
    $cachedData = $this->valkey->get($cacheKey);
    if ($cachedData) {
      $data = json_decode($cachedData, true);
      $data['source'] = 'cache';
      return $data;
    }

    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tableSeoMeta,
        $this->tableSeoMeta,
        $this->tableMain.'.id = '.$this->tableSeoMeta.'.id_parent AND '.$this->tableSeoMeta.'.type = :type'
      )
      ->where('status = :status')
      ->andWhere('slug = :slug')
      ->setParameter('status', 'active')
      ->setParameter('slug', $input['slug'])
      ->setParameter('type', 'article_category')
      ->executeQuery()
      ->fetchAssociative();

    if (!empty($query)) {
      $data = [
        'id'          => $query['id'],
        'slug'        => $query['slug'],
        'title'       => $query['title'],
        'description' => $query['description'],
        'created_at'  => date('Y-m-d H:i:s', strtotime($query['created_at'])),
        'updated_at'  => date('Y-m-d H:i:s', strtotime($query['updated_at'])),
        'seo_meta'    => [
          'meta_title'       => $query['meta_title'] ?? '',
          'meta_description' => $query['meta_description'] ?? '',
          'meta_robots'      => $query['meta_robots'] ?? '',
        ]
      ];
    }
    else {
      throw new Exception('Not Found', 404);
    }

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    $data['source'] = 'db'; // Indicate that the data is fetched from the database
    return $data;
  }
}