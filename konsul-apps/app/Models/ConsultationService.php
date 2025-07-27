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

    protected $casts = [
        'price' => 'decimal:2', // Pastikan harga di-cast ke decimal
    ];

    // --- PERBAIKAN: Relasi many-to-many ke ConsultationBooking ---
    public function consultationBookings()
    {
        return $this->belongsToMany(ConsultationBooking::class, 'booking_service', 'service_id', 'booking_id')
                    ->withPivot('price_at_booking')
                    ->withTimestamps();
    }
    // --- AKHIR PERBAIKAN ---
}
