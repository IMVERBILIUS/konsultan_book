<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationBooking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'service_id',
        'referral_code_id',
        'invoice_id',
        'booked_date',
        'booked_time',
        'contact_preference',
        'session_type',
        'offline_address',
        'discount_amount',
        'final_price',
        'payment_type',
        'session_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booked_date' => 'date',
        'booked_time' => 'datetime', // Casting to datetime might be problematic for 'time' type, consider string or custom mutator
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    /**
     * Get the user that made the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the consultation service booked.
     */
    public function service()
    {
        return $this->belongsTo(ConsultationService::class, 'service_id');
    }

    /**
     * Get the referral code used for the booking.
     */
    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    /**
     * Get the invoice associated with the booking.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
