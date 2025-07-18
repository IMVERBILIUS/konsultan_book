<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subheading extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'article_id',
        'title',
        'order_number',
    ];

    /**
     * Get the article that owns the subheading.
     */
    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the paragraphs for the subheading.
     */
    public function paragraphs()
    {
        return $this->hasMany(Paragraph::class);
    }
}
