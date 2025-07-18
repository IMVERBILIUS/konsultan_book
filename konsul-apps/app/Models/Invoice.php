<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'invoice_no',
        'invoice_date',
        'due_date',
        'total_amount',
        'payment_type',
        'payment_status',
        'session_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the invoice.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the consultation booking associated with the invoice.
     */
    public function consultationBooking()
    {
        return $this->hasOne(ConsultationBooking::class); // Asumsi 1 invoice untuk 1 booking
    }
}
