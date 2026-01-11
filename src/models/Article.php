<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model {
    protected $table = 'tb_article';
    protected $guarded = ['id'];
    public $timestamps = true;

    protected $casts = [
        'featured' => 'array',
    ];

    public function category() {
        return $this->belongsTo(ArticleCategory::class, 'id_category');
    }

    public function picture() {
        return $this->belongsTo(Picture::class, 'id_picture');
    }

    public function authorUser() {
        return $this->belongsTo(User::class, 'author'); 
    }

    public function seoMeta() {
        return $this->hasOne(SeoMeta::class, 'id_parent')->where('type', 'article');
    }
}