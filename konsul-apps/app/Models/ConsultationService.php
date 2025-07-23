<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationService extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'price',
        'short_description',
        'product_description',
        'thumbnail',
    ];

    public function consultationBookings()
    {
        return $this->hasMany(ConsultationBooking::class, 'service_id');
    }
}
