<?php

namespace App\Services\Client;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class ClientContentArticleService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_article';
  private $tablePicture = 'tb_picture';

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  private function checkExist(array $input)
  {
    $check = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->where('slug = :slug')
      ->andWhere($this->tableMain.'.status = :status')
      ->setParameter('status', 'active')
      ->setParameter('slug', $input['slug'])
      ->fetchAssociative();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function newest(array $input)
  {
    $data = [];

    $limit = $input['limit'] ?? 3;
    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        'tb_picture',
        'tb_picture',
        $this->tableMain.'.id_picture = tb_picture.id',
      )
      ->where($this->tableMain.'.status = :status')
      ->orderBy('publish', 'DESC')
      ->setMaxResults($limit)
      ->setParameter('status', 'active')
      ->executeQuery()
      ->fetchAllAssociative();

    if (!empty($query)) {
      foreach ($query as $row) {
        $cleanDesc   = strip_tags($row['description']);
        $limitedDesc = implode(' ', array_slice(explode(' ', $cleanDesc), 0, 30)) . '...';

        $data[] = [
          'slug'        => $row['slug'],
          'title'       => $row['title'],
          'description' => $limitedDesc,
          'author'      => $row['author'],
          'publish'     => date('Y-m-d H:i:s', strtotime($row['publish'])),
          'picture'     => [
            'original'  => $row['original'],
            'thumbnail' => $row['thumbnail'],
          ]
        ];
      }
    }

    return $data;
  }

  public function list(array $input)
  {
    //* INIT
    $page   = max(1, (int) ($input['page'] ?? 1));   //? default page 1
    $limit  = max(1, (int) ($input['limit'] ?? 10)); //? default 10 per page
    $offset = ($page - 1) * $limit;
    $search = $input['search'] ? $this->helper->filterSearchQuery($input['search']) : '';

    //* QUERY COUNT
    $totalCount_qb = $this->db->createQueryBuilder()
      ->select('COUNT(*) as count')
      ->from($this->tableMain)
      ->where($this->tableMain.'.status = :status')
      ->setParameter('status', 'active');
    //? IF SEARCH
    if (!empty($search)) {
      $totalCount_qb
        ->andWhere($this->tableMain.'.title LIKE :search')
        ->setParameter('search', '%'.$search.'%');
    }
    $totalCount = $totalCount_qb->executeQuery()->fetchOne();

    //* FORMAT RESULT
    $data = [
      'totalPages'  => (int) ceil(((int) $totalCount) / $limit),
      'currentPage' => $page,
      'search'      => $search,
      'list'        => []
    ];

    //* QUERY MAIN
    $queryMain_qb = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.*',
        $this->tablePicture.'.original',
        $this->tablePicture.'.thumbnail'
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tablePicture,
        $this->tablePicture,
        $this->tableMain.'.id_picture = '.$this->tablePicture.'.id',
      )
      ->where($this->tableMain.'.status = :status')
      ->orderBy('publish', 'DESC')
      ->setFirstResult($offset)
      ->setMaxResults($limit)
      ->setParameter('status', 'active');
    //? IF SEARCH
    if (!empty($search)) {
      $queryMain_qb
        ->andWhere($this->tableMain.'.title LIKE :search')
        ->setParameter('search', '%'.$search.'%');
    }
    $queryMain = $queryMain_qb->executeQuery()->fetchAllAssociative();

    //* RESULT
    if (!empty($queryMain)) {
      foreach ($queryMain as $row) {
        $cleanDesc   = strip_tags($row['description']);
        $limitedDesc = implode(' ', array_slice(explode(' ', $cleanDesc), 0, 30)) . '...';

        $data['list'][] = [
          'slug'        => $row['slug'],
          'title'       => $row['title'],
          'description' => $limitedDesc,
          'author'      => $row['author'],
          'publish'     => date('Y-m-d H:i:s', strtotime($row['publish'])),
          'picture'     => [
            'original'  => $row['original'],
            'thumbnail' => $row['thumbnail'],
          ]
        ];
      }
    }

    return $data;
  }

  public function detail(array $input)
  {
    $data = [
      'detail' => [],
    ];
    $check = $this->checkExist($input);

    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        'tb_picture',
        'tb_picture',
        $this->tableMain.'.id_picture = tb_picture.id',
      )
      ->where('slug = :slug')
      ->andWhere($this->tableMain.'.status = :status')
      ->setParameter('status', 'active')
      ->setParameter('slug', $check['slug'])
      ->executeQuery()
      ->fetchAssociative();

    if (!empty($query)) {
      $data['detail'] = [
        'slug'        => $query['slug'],
        'title'       => $query['title'],
        'description' => $query['description'],
        'author'      => $query['author'],
        'publish'     => date('Y-m-d H:i:s', strtotime($query['publish'])),
        'picture'     => [
          'original'  => $query['original'],
          'thumbnail' => $query['thumbnail'],
        ]
      ];
    }

    return $data;
  }
}