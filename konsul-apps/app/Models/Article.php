<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        // 'author', // author bisa jadi field terpisah atau dari relasi user
        'description',
        'thumbnail',
        'status',
        'views',
        'published_at',
    ];

    /**
     * Get the user that owns the article (the author).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subheadings for the article.
     */
    public function subheadings()
    {
        return $this->hasMany(Subheading::class);
    }

    /**
     * Get the comments for the article.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Opsional: Accessor untuk mendapatkan URL thumbnail
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        return null;
    }
}
