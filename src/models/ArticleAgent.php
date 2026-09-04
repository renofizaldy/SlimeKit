<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleAgent extends Model {
    protected $table = 'tb_article_agent';
    protected $guarded = [];
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'brainstorming_options' => 'array',
        'selected_option_data' => 'array',
    ];

    public function article() {
        return $this->belongsTo(Article::class, 'id_parent');
    }
}
