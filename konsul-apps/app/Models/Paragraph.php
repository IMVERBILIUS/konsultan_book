<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paragraph extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subheading_id',
        'content',
        'order_number',
    ];

    /**
     * Get the subheading that owns the paragraph.
     */
    public function subheading()
    {
        return $this->belongsTo(Subheading::class);
    }
}
