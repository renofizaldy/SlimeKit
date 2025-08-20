<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class AdminStatsService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableLog = 'tb_log';

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  public function listLog(array $input)
  {
    $data = [];

    $qb = $this->db->createQueryBuilder()
      ->select([
        $this->tableLog.'.table_name AS title',
        $this->tableLog.'.action',
        $this->tableLog.'.created_at AS time',
        $this->tableLog.'.id_record',
        'COALESCE(tb_user.name, :guest) AS user'
      ])
      ->from($this->tableLog)
      ->leftJoin(
        $this->tableLog,
        'tb_user',
        'tb_user',
        'tb_user.id = '.$this->tableLog.'.id_user'
      )
      ->setParameter('guest', 'Guest')
      ->orderBy($this->tableLog.'.created_at', 'DESC')
      ->setMaxResults($input['limit']);

    if (!empty($input['table'])) {
      $qb->andWhere($this->tableLog.'.table_name = :table_name')
        ->setParameter('table_name', $input['table']);
    }
    if (!empty($input['user'])) {
      $qb->andWhere($this->tableLog.'.id_user = :id_user')
        ->setParameter('id_user', $input['user']);
    }
    if (!empty($input['action'])) {
      $qb->andWhere($this->tableLog.'.action = :action')
        ->setParameter('action', $input['action']);
    }

    $query = $qb->executeQuery()->fetchAllAssociative();

    if (!empty($query)) {
      $actionMap = [
        'INSERT' => 'create',
        'UPDATE' => 'update',
        'DELETE' => 'drop',
      ];

      $titleMap = [
        'tb_article'          => ['title' => 'Article', 'caption' => 'Article' ],
        'tb_article_category' => ['title' => 'Article Category', 'caption' => 'Category' ],
        'tb_seo_meta'         => ['title' => 'SEO Meta', 'caption' => 'Meta Tags' ],
        'tb_content_gallery'  => ['title' => 'Content: Gallery', 'caption' => 'Image' ],
        'tb_content_faq'      => ['title' => 'Content: FAQ', 'caption' => 'FAQ' ],
        'tb_content_team'     => ['title' => 'Content: Team', 'caption' => 'Team' ],
        'tb_content_contact'  => ['title' => 'Content: Contact', 'caption' => 'Contact' ],
        'tb_user'             => ['title' => 'Setting: User Account', 'caption' => 'User' ],
        'tb_user_role'        => ['title' => 'Setting: User Role', 'caption' => 'User Role' ],
      ];

      $contentMap = [
        'tb_article'          => 'title',
        'tb_article_category' => 'title',
        'tb_seo_meta'         => 'meta_title',
        'tb_content_gallery'  => 'name',
        'tb_content_faq'      => 'title',
        'tb_content_team'     => 'name',
        'tb_content_contact'  => 'name',
        'tb_user'             => 'name',
        'tb_user_role'        => 'label',
      ];

      $data = array_map(function ($log) use ($actionMap, $titleMap, $contentMap) {
        $content = null;

        if (!empty($log['title']) && isset($contentMap[$log['title']]) && !empty($log['id_record'])) {
          $table = $log['title'];
          $column = $contentMap[$table];

          try {
            $row = $this->db->createQueryBuilder()
              ->select($column)
              ->from($table)
              ->where('id = :id')
              ->setParameter('id', $log['id_record'])
              ->executeQuery()
              ->fetchAssociative();

            if ($row && isset($row[$column])) {
              $content = $row[$column];
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
    }

    return $data;
  }

  public function dropLog(array $input)
  {
    $this->db->beginTransaction();
    try {
      //? DELETE table
        $this->db->createQueryBuilder()
          ->delete($this->tableLog)
          ->where('id = :id')
          ->setParameter('id', $input['id'])
          ->executeStatement();
      //? DELETE table

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function listCronArticle(array $input)
  {
    $queryBuilder = $this->db->createQueryBuilder()
      ->select(
        "{$this->tableArticle}.*",
        "{$this->tableCronhooks}.id_cronhooks"
      )
      ->from($this->tableArticle)
      ->leftJoin(
        $this->tableArticle,
        $this->tableCronhooks,
        $this->tableCronhooks,
        "{$this->tableArticle}.id = {$this->tableCronhooks}.id_parent AND {$this->tableCronhooks}.type = 'article'"
      )
      ->where("{$this->tableArticle}.status = 'inactive'")
      ->andWhere("{$this->tableArticle}.publish > NOW()")
      ->orderBy("{$this->tableArticle}.publish", "ASC");
    $query = $queryBuilder->executeQuery()->fetchAllAssociative();
    $data  = (!empty($query)) ? $query : [];
    return $data;
  }
}