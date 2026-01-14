<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Models\User;
use App\Models\UserRole;

class AdminAuthService
{
  private $helper;

  public function __construct()
  {
    $this->helper = new General;
  }

  public function login(array $input)
  {
    //* CHECK USER
      $username = filter_var($input['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $password = $input['password'];
      $user     = User::with('userRole')
        ->where('username', $username)
        ->where('status', 'active')
        ->first();
      if (!$user || !password_verify($password, $user->password)) {
        throw new Exception('Username atau password salah', 401);
      }
      $getData          = $user->toArray();
      $getData['label'] = $user->userRole->label ?? null;
      $getData['role']  = $user->userRole->role ?? null;
    //* CHECK USER

    DB::beginTransaction();
    try {
      //* SET LAST LOGIN
        $user->update(['last_login' => date('Y-m-d H:i:s')]);
      //* SET LAST LOGIN

      //* GET ROLE
        $getPeran = UserRole::where('id', $getData['id_user_role'])->value('role');
      //* GET ROLE

      DB::commit();

      return [
        'user' => $getData,
        'role' => $getPeran
      ];
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function auth(array $input)
  {
    $user = User::with('userRole')
      ->where('id', $input['user_id'])
      ->where('status', 'active')
      ->first();
    if (!$user) {
      throw new Exception('Unauthenticated', 401);
    }
    $users = $user->toArray();
    $users['label'] = $user->userRole->label ?? null;
    $users['role']  = $user->userRole->role ?? null;
    return $users;
  }
}