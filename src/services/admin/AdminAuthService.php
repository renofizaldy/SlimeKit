<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;

class AdminAuthService
{
  private $db;
  private $helper;

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
  }

  public function login(array $input)
  {
    //* CHECK USER
      $username = filter_var($input['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $password = $input['password'];
      $query    = $this->db->createQueryBuilder()
        ->select('
          tb_user.*,
          tb_user_role.label as label,
          tb_user_role.role as role'
        )
        ->from('tb_user')
        ->innerJoin(
          'tb_user',
          'tb_user_role',
          'tb_user_role',
          'tb_user.id_user_role = tb_user_role.id'
        )
        ->where('username = :username')
        ->andWhere('status = :status')
        ->setParameter('username', $username)
        ->setParameter('status', 'active');
      $getData = $query->executeQuery()->fetchAssociative();
      if (!$getData || !password_verify($password, $getData['password'])) {
        throw new Exception('Username atau password salah', 401);
      }
    //* CHECK USER

    $this->db->beginTransaction();
    try {
      //* SET LAST LOGIN
        $this->db->update('tb_user', ['last_login' => date('Y-m-d H:i:s')], ['id' => $getData['id']]);
      //* SET LAST LOGIN

      //* GET ROLE
        $queryPeran = $this->db->createQueryBuilder()
          ->select('role')
          ->from('tb_user_role')
          ->where('id = :id')
          ->setParameter('id', $getData['id_user_role']);
        $getPeran = $queryPeran->executeQuery()->fetchOne();
      //* GET ROLE

      $this->db->commit();

      return [
        'user' => $getData,
        'role' => $getPeran
      ];
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function auth(array $input)
  {
    $users = $this->db->createQueryBuilder()
      ->select('
        tb_user.*,
        tb_user_role.label as label,
        tb_user_role.role as role'
      )
      ->from('tb_user')
      ->innerJoin(
        'tb_user',
        'tb_user_role',
        'tb_user_role',
        'tb_user.id_user_role = tb_user_role.id'
      )
      ->where('tb_user.id = :staff_id')
      ->andWhere('tb_user.status = :status')
      ->setParameter('staff_id', $input['user_id'])
      ->setParameter('status', 'active')
      ->executeQuery()
      ->fetchAssociative();
    if (!$users) {
      throw new Exception('Unauthenticated', 401);
    }
    return $users;
  }
}