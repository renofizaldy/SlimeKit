<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Models\User;
use App\Models\UserRole;

class AdminSettingUserService
{
  private $db;
  private $helper;
  private $tableMain = 'tb_user';
  private $tableRole = 'tb_user_role';

  public function __construct()
  {
    $this->helper = new General;
  }

  public function checkExist(array $input)
  {
    $check = User::where('id', $input['id'])->first();
    if (!$check) {
      throw new Exception('User does not exist', 404);
    }
    return $check;
  }

  public function list()
  {
    $data  = [];
    $query = User::with(['userRole:id,label'])->get();
    if (!$query->isEmpty()) {
      foreach ($query as $row) {
        $data[] = [
          'id'         => $row->id,
          'fullname'   => $row->name,
          'telepon'    => $row->phone,
          'email'      => $row->email,
          'username'   => $row->username,
          'role'       => $row->id_user_role,
          'peran'      => $row->userRole->label ?? null,
          'status'     => $row->status,
          'last_login' => $row->last_login,
        ];
      }
    }
    return $data;
  }

  public function add(array $input, array $user)
  {
    //* CHECK IF USERNAME ALREADY EXISTS
      $existUser = User::where('username', $input['username'])->exists();
      if ($existUser) {
        throw new Exception('Username already exists', 409);
      }
    //* CHECK IF USERNAME ALREADY EXISTS

    DB::beginTransaction();
    try {
      $insert = User::create([
        'name'          => $input['fullname'],
        'username'      => $input['username'],
        'password'      => password_hash($input['password'], PASSWORD_DEFAULT),
        'status'        => $input['status'],
        'id_user_role'  => $input['role'],
        'last_login'    => date('Y-m-d H:i:s'),
      ]);

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $insert->id, 'INSERT');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      User::where('id', $check->id)->update([
        'name'         => $input['fullname'],
        'username'     => $input['username'],
        'id_user_role' => $input['role'],
        'status'       => $input['status'],
      ]);

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $check['id'], 'UPDATE');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function pass(array $input, array $user)
  {
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      User::where('id', $check->id)->update([
        'password' => password_hash($input['password'], PASSWORD_DEFAULT),
      ]);

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $check['id'], 'UPDATE');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function drop(array $input, array $user)
  {
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      User::where('id', $check->id)->delete();

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'DELETE');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function roles()
  {
    $query = UserRole::get()->toArray();
    return $query;
  }

  public function addRoles(array $input, array $user)
  {
    DB::beginTransaction();
    try {
      $insert = UserRole::create([
        'label'      => $input['label'],
        'role'       => json_encode($input['role']),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);

      //? LOG Record
        $this->helper->addLog($user, $this->tableRole, $insert->id, 'INSERT');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function editRoles(array $input, array $user)
  {
    DB::beginTransaction();
    try {
      UserRole::where('id', $input['id'])->update([
        'label'      => $input['label'],
        'role'       => json_encode($input['role']),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);

      //? LOG Record
        $this->helper->addLog($user, $this->tableRole, $input['id'], 'UPDATE');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function dropRoles(array $input, array $user)
  {
    //* CHECK USER RELATION
      $check = User::where('id_user_role', $input['id'])->exists();
      if ($check) {
        throw new Exception('Role is in use', 409);
      }
    //* CHECK USER RELATION

    DB::beginTransaction();
    try {
      UserRole::where('id', $input['id'])->delete();

      //? LOG Record
        $this->helper->addLog($user, $this->tableRole, (int) $input['id'], 'DELETE');
      //? LOG Record

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}