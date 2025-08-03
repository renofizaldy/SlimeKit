<?php

namespace App\Services\Client;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;

class ClientArticleService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $valkey;
  private $tableMain = 'tb_article';
  private $tableCategory = 'tb_article_category';
  private $tablePicture = 'tb_picture';
  private $tableSeoMeta = 'tb_seo_meta';
  private $cacheKey = 'article';
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
    //* INIT
    $page   = max(1, (int) ($input['page'] ?? 1));   //? default page 1
    $limit  = max(1, (int) ($input['limit'] ?? 10)); //? default 10 per page
    $offset = ($page - 1) * $limit;
    $search = $input['search'] ? $this->helper->filterSearchQuery($input['search']) : '';

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

    //* QUERY COUNT
    $totalCount_qb = $this->db->createQueryBuilder()
      ->select('COUNT(*) as count')
      ->from($this->tableMain)
      ->where($this->tableMain.'.status = :status')
      ->setParameter('status', 'active')
      ->setParameter('type', 'article');
    //? IF FEATURED
    if (!empty($input['featured'])) {
      $totalCount_qb
        ->andWhere(":featured = ANY(".$this->tableMain.".featured)")
        ->setParameter('featured', $input['featured']);
    }
    //? IF CATEGORY
    if (!empty($input['category'])) {
      $totalCount_qb
        ->innerJoin(
          $this->tableMain,
          $this->tableCategory,
          $this->tableCategory,
          $this->tableMain.'.id_category = '.$this->tableCategory.'.id'
        )
        ->andWhere($this->tableCategory.'.slug = :slug')
        ->andWhere($this->tableCategory.'.status = :status')
        ->setParameter('slug', $input['category'])
        ->setParameter('status', 'active');
    }
    //? IF SEARCH
    if (!empty($search)) {
      $totalCount_qb
        ->andWhere(
          $totalCount_qb->expr()->or(
            $totalCount_qb->expr()->like('LOWER('.$this->tableMain.'.title)', ':search'),
            $totalCount_qb->expr()->like('LOWER('.$this->tableMain.'.excerpt)', ':search')
          )
        )
        ->setParameter('search', '%'.$search.'%');
    }
    $totalCount = $totalCount_qb->executeQuery()->fetchOne();

    //* FORMAT RESULT
    $data = [
      'totalPages'  => (int) ceil(((int) $totalCount) / $limit),
      'currentPage' => $page,
      'search'      => $search,
      'category'    => $input['category'] ?? '',
      'featured'    => $input['featured'] ?? '',
      'list'        => []
    ];

    //* QUERY MAIN
    $queryMain_qb = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.*',
        $this->tablePicture.'.original',
        $this->tablePicture.'.thumbnail',
        $this->tablePicture.'.caption',
        $this->tableSeoMeta.'.meta_title',
        $this->tableSeoMeta.'.meta_description',
        $this->tableSeoMeta.'.meta_robots',
        $this->tableCategory.'.slug AS category_slug',
        $this->tableCategory.'.title AS category_title',
        $this->tableCategory.'.description AS category_description'
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tablePicture,
        $this->tablePicture,
        $this->tableMain.'.id_picture = '.$this->tablePicture.'.id',
      )
      ->leftJoin(
        $this->tableMain,
        $this->tableSeoMeta,
        $this->tableSeoMeta,
        $this->tableMain.'.id = '.$this->tableSeoMeta.'.id_parent AND '.$this->tableSeoMeta.'.type = :type',
      )
      ->where($this->tableMain.'.status = :status')
      ->setFirstResult($offset)
      ->setMaxResults($limit)
      ->setParameter('status', 'active')
      ->setParameter('type', 'article');
    //? IF FEATURED
    if (!empty($input['featured'])) {
      $queryMain_qb
        ->andWhere(":featured = ANY(".$this->tableMain.".featured)")
        ->setParameter('featured', $input['featured']);
    }
    //? IF CATEGORY
    if (!empty($input['category'])) {
      $queryMain_qb
        ->innerJoin(
          $this->tableMain,
          $this->tableCategory,
          $this->tableCategory,
          $this->tableMain.'.id_category = '.$this->tableCategory.'.id AND '.$this->tableCategory.'.status = :status'
        )
        ->andWhere($this->tableCategory.'.slug = :slug')
        ->setParameter('slug', $input['category'])
        ->setParameter('status', 'active');
    } else {
      $queryMain_qb
        ->leftJoin(
          $this->tableMain,
          $this->tableCategory,
          $this->tableCategory,
          $this->tableMain.'.id_category = '.$this->tableCategory.'.id AND '.$this->tableCategory.'.status = :status'
        )
        ->setParameter('status', 'active');
    }
    //? IF ORDER BY
    if (!empty($input['order_by']) && !empty($input['order_type'])) {
      //! VALIDATE ORDER
        $allowedOrderBy = ['id', 'status', 'slug', 'title', 'content', 'excerpt', 'author', 'publish', 'featured', 'read_time', 'created_at', 'updated_at', 'category_slug', 'category_title'];
        if (!in_array($input['order_by'], $allowedOrderBy)) {
          throw new Exception('Invalid order by field.', 400);
        }
        if ($input['order_type'] !== 'asc' && $input['order_type'] !== 'desc') {
          throw new Exception('Invalid order type.', 400);
        }
      //! VALIDATE ORDER
      $orderBy   = $input['order_by'];
      $orderType = $input['order_type'];
      $queryMain_qb->orderBy($this->tableMain.'.'.$orderBy, $orderType);
    }
    else {
      $queryMain_qb->orderBy($this->tableMain.'.publish', 'DESC');
    }
    //? IF SEARCH
    if (!empty($search)) {
      $queryMain_qb
        ->andWhere(
          $queryMain_qb->expr()->or(
            $queryMain_qb->expr()->like('LOWER('.$this->tableMain.'.title)', ':search'),
            $queryMain_qb->expr()->like('LOWER('.$this->tableMain.'.excerpt)', ':search')
          )
        )
        ->setParameter('search', '%'.$search.'%');
    }
    $queryMain = $queryMain_qb->executeQuery()->fetchAllAssociative();

    //* RESULT
    if (!empty($queryMain)) {
      foreach ($queryMain as $row) {
        $data['list'][] = [
          'slug'        => $row['slug'],
          'title'       => $row['title'],
          'description' => strip_tags($row['excerpt']),
          'author'      => $row['author'],
          'read_time'   => $row['read_time'] ?? 0,
          'publish'     => date('Y-m-d H:i:s', strtotime($row['publish'])),
          'featured'    => $row['featured'] ?? [],
          'picture'     => [
            'original'  => $row['original'],
            'thumbnail' => $row['thumbnail'],
            'caption'   => $row['caption'] ?? '',
          ],
          'category'    => [
            'slug'        => $row['category_slug'] ?? '',
            'title'       => $row['category_title'] ?? '',
            'description' => $row['category_description'] ?? '',
          ],
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

    $query = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.*',
        $this->tablePicture.'.original',
        $this->tablePicture.'.thumbnail',
        $this->tablePicture.'.caption',
        $this->tableSeoMeta.'.meta_title',
        $this->tableSeoMeta.'.meta_description',
        $this->tableSeoMeta.'.meta_robots',
        $this->tableCategory.'.slug AS category_slug',
        $this->tableCategory.'.title AS category_title',
        $this->tableCategory.'.description AS category_description'
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tablePicture,
        $this->tablePicture,
        $this->tableMain.'.id_picture = '.$this->tablePicture.'.id',
      )
      ->leftJoin(
        $this->tableMain,
        $this->tableSeoMeta,
        $this->tableSeoMeta,
        $this->tableMain.'.id = '.$this->tableSeoMeta.'.id_parent AND '.$this->tableSeoMeta.'.type = :type',
      )
      ->leftJoin(
        $this->tableMain,
        $this->tableCategory,
        $this->tableCategory,
        $this->tableMain.'.id_category = '.$this->tableCategory.'.id AND '.$this->tableCategory.'.status = :status'
      )
      ->where($this->tableMain.'.status = :status')
      ->andWhere($this->tableMain.'.slug = :slug')
      ->setParameter('status', 'active')
      ->setParameter('slug', $input['slug'])
      ->setParameter('type', 'article')
      ->executeQuery()
      ->fetchAssociative();

    if (!empty($query)) {
      $data['detail'] = [
        'slug'      => $query['slug'],
        'title'     => $query['title'],
        'excerpt'   => $query['excerpt'],
        'content'   => $query['content'],
        'author'    => $query['author'],
        'read_time' => $query['read_time'] ?? 0,
        'publish'   => date('Y-m-d H:i:s', strtotime($query['publish'])),
        'updated'   => date('Y-m-d H:i:s', strtotime($query['updated_at'])),
        'picture'   => [
          'original'  => $query['original'],
          'thumbnail' => $query['thumbnail'],
          'caption'   => $query['caption'],
        ],
        'category'  => [
          'slug'        => $query['category_slug'] ?? '',
          'title'       => $query['category_title'] ?? '',
          'description' => $query['category_description'] ?? '',
        ],
        'seo_meta'  => [
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

    $data['source'] = 'db';
    return $data;
  }
}