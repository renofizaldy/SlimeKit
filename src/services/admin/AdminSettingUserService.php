<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;

class AdminSettingUserService
{
  private $db;
  private $helper;
  private $tableMain = 'tb_user';

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
  }

  public function checkExist(array $input)
  {
    $check = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->where('id = :id')
      ->setParameter('id', (int) $input['id'])
      ->fetchAssociative();
    if (!$check) {
      throw new Exception('User does not exist', 404);
    } else {
      return $check;
    }
  }

  public function list()
  {
    $data  = [];
    $query = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.*',
        'tb_user_role.label AS peran'
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        'tb_user_role',
        'tb_user_role',
        $this->tableMain.'.id_user_role = tb_user_role.id'
      )
      ->executeQuery()
      ->fetchAllAssociative();
    if (!empty($query)) {
      foreach ($query as $row) {
        $data[] = [
          'id'         => $row['id'],
          'fullname'   => $row['name'],
          'telepon'    => $row['phone'],
          'email'      => $row['email'],
          'username'   => $row['username'],
          'role'       => $row['id_user_role'],
          'peran'      => $row['peran'],
          'status'     => $row['status'],
          'last_login' => $row['last_login'],
        ];
      }
    }

    return $data;
  }

  public function add(array $input, array $user)
  {
    //* CHECK IF USERNAME ALREADY EXISTS
      $existingUser = $this->db->createQueryBuilder()
        ->select('id')
        ->from($this->tableMain)
        ->where('username = :username')
        ->setParameter('username', $input['username'])
        ->executeQuery()
        ->fetchAssociative();
      if (!empty($existingUser)) {
        throw new Exception('Username already exists', 409);
      }
    //* CHECK IF USERNAME ALREADY EXISTS

    $this->db->beginTransaction();
    try {
      $this->db->createQueryBuilder()
        ->insert($this->tableMain)
        ->setValue('name', ':fullname')
        ->setValue('username', ':username')
        ->setValue('password', ':password')
        ->setValue('status', ':status')
        ->setValue('id_user_role', ':role')
        ->setValue('last_login', ':last_login')
        ->setValue('created_at', ':created_at')
        ->setValue('updated_at', ':updated_at')
        ->setParameter('fullname', $input['fullname'])
        ->setParameter('username', $input['username'])
        ->setParameter('password', password_hash($input['password'], PASSWORD_DEFAULT))
        ->setParameter('status', $input['status'])
        ->setParameter('role', $input['role'])
        ->setParameter('last_login', date('Y-m-d H:i:s'))
        ->setParameter('created_at', date('Y-m-d H:i:s'))
        ->setParameter('updated_at', date('Y-m-d H:i:s'))
        ->executeStatement();

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, $this->db->lastInsertId(), 'INSERT');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    $check = $this->checkExist($input);

    $this->db->beginTransaction();
    try {
      $this->db->createQueryBuilder()
        ->update($this->tableMain)
        ->set('name', ':fullname')
        ->set('username', ':username')
        ->set('id_user_role', ':role')
        ->set('status', ':status')
        ->set('updated_at', ':updated_at')
        ->where('id = :id')
        ->setParameter('fullname', $input['fullname'])
        ->setParameter('username', $input['username'])
        ->setParameter('role', $input['role'])
        ->setParameter('status', $input['status'])
        ->setParameter('updated_at', date('Y-m-d H:i:s'))
        ->setParameter('id', $check['id'])
        ->executeStatement();

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, $check['id'], 'UPDATE');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function pass(array $input, array $user)
  {
    $check = $this->checkExist($input);

    $this->db->beginTransaction();
    try {
      $this->db->createQueryBuilder()
        ->update($this->tableMain)
        ->set('password', ':password')
        ->set('updated_at', ':updated_at')
        ->where('id = :id')
        ->setParameter('password', password_hash($input['password'], PASSWORD_DEFAULT))
        ->setParameter('updated_at', date('Y-m-d H:i:s'))
        ->setParameter('id', $check['id'])
        ->executeStatement();

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, $check['id'], 'UPDATE');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function drop(array $input, array $user)
  {
    $this->checkExist($input);

    $this->db->beginTransaction();
    try {
      $this->db->createQueryBuilder()
        ->delete($this->tableMain)
        ->where('id = :id')
        ->setParameter('id', (int) $input['id'])
        ->executeStatement();

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, (int) $input['id'], 'DELETE');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function roles()
  {
    $data  = [];
    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from('tb_user_role')
      ->executeQuery()
      ->fetchAllAssociative();
    if (!empty($query)) {
      foreach ($query as $row) {
        $data[] = $row;
      }
    }
    return $data;
  }

  public function addRoles(array $input, array $user)
  {
    $this->db->beginTransaction();
    try {
      $this->db->createQueryBuilder()
        ->insert('tb_user_role')
        ->values([
          'label'      => ':label',
          'role'       => ':role',
          'created_at' => ':created_at',
          'updated_at' => ':updated_at'
        ])
        ->setParameter('label', $input['label'])
        ->setParameter('role', json_encode($input['role']))
        ->setParameter('created_at', date('Y-m-d H:i:s'))
        ->setParameter('updated_at', date('Y-m-d H:i:s'))
        ->executeStatement();

      //? LOG Record
        $this->helper->addLog($this->db, $user, 'tb_user_role', $this->db->lastInsertId(), 'INSERT');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function editRoles(array $input, array $user)
  {
    $this->db->beginTransaction();
    try {
      $this->db->createQueryBuilder()
        ->update('tb_user_role')
        ->set('label', ':label')
        ->set('role', ':role')
        ->set('updated_at', ':updated_at')
        ->where('id = :id')
        ->setParameter('label', $input['label'])
        ->setParameter('role', json_encode($input['role']))
        ->setParameter('updated_at', date('Y-m-d H:i:s'))
        ->setParameter('id', $input['id'])
        ->executeStatement();

      //? LOG Record
        $this->helper->addLog($this->db, $user, 'tb_user_role', $input['id'], 'UPDATE');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function dropRoles(array $input, array $user)
  {
    //* CHECK USER RELATION
    $check = $this->db->createQueryBuilder()
      ->select('id')
      ->from($this->tableMain)
      ->where('id_user_role = :id')
      ->setParameter('id', (int) $input['id'])
      ->executeQuery()
      ->fetchAssociative();
    if (!empty($check)) {
      throw new Exception('Role is in use', 409);
    }

    $this->db->beginTransaction();
    try {
      $this->db->createQueryBuilder()
        ->delete('tb_user_role')
        ->where('id = :id')
        ->setParameter('id', (int) $input['id'])
        ->executeStatement();

      //? LOG Record
        $this->helper->addLog($this->db, $user, 'tb_user_role', (int) $input['id'], 'DELETE');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }
}