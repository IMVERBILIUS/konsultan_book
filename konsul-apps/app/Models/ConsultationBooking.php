<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receiver_name',
        'referral_code_id',
        'invoice_id',
        'contact_preference',
        'discount_amount',
        'final_price',
        'payment_type',
        'session_status',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->belongsToMany(ConsultationService::class, 'booking_service', 'booking_id', 'service_id')
                    ->withPivot('price_at_booking', 'booked_date', 'booked_time', 'session_type', 'offline_address')
                    ->withTimestamps();
    }

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
