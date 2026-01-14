<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
  protected $table = 'tb_user';
  protected $guarded = ['id'];
  public $timestamps = false;

  public function userRole() {
    return $this->belongsTo(UserRole::class, 'id_user_role');
  }
}