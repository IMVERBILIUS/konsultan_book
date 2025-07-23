<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConsultationBooking;
use App\Models\ConsultationService;
use App\Models\ReferralCode;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ConsultationBookingController extends Controller
{
    const OFFLINE_ADDRESS_DEFAULT = "Jl. Sadar Dusun I Kampung Padang, Riau - 28557";

    public function index(Request $request)
    {
        try {
            $bookings = ConsultationBooking::with(['user', 'service', 'referralCode', 'invoice'])
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(10);
            return response()->json($bookings);
        } catch (\Exception $e) {
            Log::error('Error fetching consultation bookings: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to fetch consultation bookings.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'service_id' => 'required|exists:consultation_services,id',
                'booked_date' => 'required|date|after_or_equal:today',
                'booked_time' => 'required|date_format:H:i',
                'contact_preference' => 'required|in:chat_only,chat_and_call',
                'session_type' => 'required|in:online,offline',
                'offline_address' => 'nullable|string|required_if:session_type,offline',
                'referral_code' => 'nullable|string|exists:referral_codes,code',
                'payment_type' => 'required|in:dp,full_payment',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (Booking store): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $service = ConsultationService::find($request->service_id);
            if (!$service) {
                throw new \Exception('Layanan konsultasi tidak ditemukan.');
            }

            $totalPrice = $service->price;
            $discountAmount = 0;
            $referralCodeId = null;

            if ($request->referral_code) {
                $referralCode = ReferralCode::where('code', $request->referral_code)
                                            ->where(function($query) {
                                                $query->whereNull('valid_from')
                                                      ->orWhere('valid_from', '<=', now());
                                            })
                                            ->where(function($query) {
                                                $query->whereNull('valid_until')
                                                      ->orWhere('valid_until', '>=', now());
                                            })
                                            ->first();

                if ($referralCode && ($referralCode->max_uses === null || $referralCode->current_uses < $referralCode->max_uses)) {
                    $discountPercentage = $referralCode->discount_percentage; // Simpan persentase
                    $discountAmount = $totalPrice * ($discountPercentage / 100);
                    $referralCodeId = $referralCode->id;
                    $referralCode->increment('current_uses');
                    Log::info('DEBUG LARAVEL: Referral code ' . $referralCode->code . ' applied. Discount: ' . $discountAmount);
                } else {
                    Log::warning('DEBUG LARAVEL: Invalid or expired referral code: ' . $request->referral_code);
                }
            }

            $finalPrice = $totalPrice - $discountAmount;
            $amountToPay = $request->payment_type === 'dp' ? $finalPrice / 2 : $finalPrice;

            $invoice = new Invoice();
            $invoice->user_id = $request->user()->id;
            $invoice->invoice_no = 'INV-' . Str::upper(Str::random(8)) . '-' . now()->format('Ymd');
            $invoice->invoice_date = now();
            $invoice->due_date = now()->addDay();
            $invoice->total_amount = $amountToPay; // Total yang harus dibayar sekarang
            $invoice->payment_type = $request->payment_type;
            $invoice->payment_status = 'unpaid';
            $invoice->session_type = $request->session_type;
            $invoice->save();
            Log::info('DEBUG LARAVEL: Invoice created: ' . $invoice->invoice_no . ' for amount: ' . $invoice->total_amount);

            $booking = ConsultationBooking::create([
                'user_id' => $request->user()->id,
                'service_id' => $request->service_id,
                'referral_code_id' => $referralCodeId,
                'invoice_id' => $invoice->id,
                'booked_date' => $request->booked_date,
                'booked_time' => $request->booked_time,
                'contact_preference' => $request->contact_preference,
                'session_type' => $request->session_type,
                'offline_address' => $request->session_type === 'offline' ? self::OFFLINE_ADDRESS_DEFAULT : null,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice, // Harga final setelah diskon (sebelum DP)
                'payment_type' => $request->payment_type,
                'session_status' => 'menunggu pembayaran',
            ]);
            Log::info('DEBUG LARAVEL: Booking created for service ID ' . $request->service_id . '. Booking ID: ' . $booking->id);

            DB::commit();

            return response()->json([
                'message' => 'Booking berhasil dibuat!',
                'booking' => $booking->load(['user', 'service', 'invoice']), // Load relasi untuk respons
                'invoice' => $invoice
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation Error (Booking store): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating booking: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal membuat booking: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tampilkan satu booking (INI BUKAN showInvoice lagi, ini show standar apiResource).
     *
     * @param  int  $id (Booking ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        \Log::info('DEBUG LARAVEL CONTROLLER [BOOKING SHOW ADMIN]: Incoming request for show with ID: ' . $id);
        if (!is_numeric($id) || $id <= 0) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW ADMIN]: Invalid ID received: ' . $id);
            return response()->json(['message' => 'ID booking tidak valid.'], 400);
        }

        $booking = ConsultationBooking::with(['user', 'service', 'referralCode', 'invoice'])->find($id);
        if (!$booking) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW ADMIN]: Booking ID ' . $id . ' not found for admin view.');
            return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
        }

        \Log::info('DEBUG LARAVEL CONTROLLER [BOOKING SHOW ADMIN]: Booking ID ' . $id . ' found.');
        return response()->json(['message' => 'Booking berhasil ditemukan.', 'booking' => $booking]);
    }

    /**
     * Tampilkan detail invoice untuk booking tertentu (metode kustom).
     * Dipanggil oleh rute admin/consultation-bookings/{bookingId}/invoice.
     *
     * @param  int  $bookingId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showInvoice($bookingId)
    {
        // --- DEBUGGING: Log showInvoice ---
        \Log::info('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Incoming request for showInvoice with Booking ID: ' . $bookingId);
        if (!is_numeric($bookingId) || $bookingId <= 0) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Invalid Booking ID received: ' . $bookingId);
            return response()->json(['message' => 'ID booking tidak valid.'], 400);
        }
        // --- AKHIR DEBUGGING ---

        $booking = ConsultationBooking::with(['invoice.user', 'service', 'user', 'referralCode'])->find($bookingId);
        if (!$booking) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Booking ID ' . $bookingId . ' not found for invoice view.');
            return response()->json(['message' => 'Booking tidak ditemukan untuk invoice ini.'], 404);
        }

        if (!$booking->invoice) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Invoice not found for Booking ID: ' . $bookingId);
            return response()->json(['message' => 'Invoice tidak ditemukan untuk booking ini.'], 404);
        }

        \Log::info('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Invoice found for Booking ID ' . $bookingId . '. Invoice No: ' . $booking->invoice->invoice_no);
        return response()->json(['message' => 'Invoice berhasil ditemukan.', 'invoice' => $booking->invoice->load('user')]); // Load user dari invoice
    }


    /**
     * Perbarui booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $booking = ConsultationBooking::find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
        }

        try {
            $request->validate([
                'service_id' => 'sometimes|required|exists:consultation_services,id',
                'booked_date' => 'sometimes|required|date|after_or_equal:today',
                'booked_time' => 'sometimes|required|date_format:H:i',
                'contact_preference' => 'sometimes|required|in:chat_only,chat_and_call',
                'session_type' => 'sometimes|required|in:online,offline',
                'offline_address' => 'nullable|string|required_if:session_type,offline',
                'referral_code' => 'nullable|string|exists:referral_codes,code',
                'payment_type' => 'sometimes|required|in:dp,full_payment',
                'session_status' => 'sometimes|required|in:menunggu pembayaran,terdaftar,ongoing,selesai,dibatalkan',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (Booking update): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $booking->service_id = $request->service_id ?? $booking->service_id;
            $booking->booked_date = $request->booked_date ?? $booking->booked_date;
            $booking->booked_time = $request->booked_time ?? $booking->booked_time;
            $booking->contact_preference = $request->contact_preference ?? $booking->contact_preference;
            $booking->session_type = $request->session_type ?? $booking->session_type;
            $booking->offline_address = $request->session_type === 'offline' ? self::OFFLINE_ADDRESS_DEFAULT : null;
            $booking->payment_type = $request->payment_type ?? $booking->payment_type;
            $booking->session_status = $request->session_status ?? $booking->session_status;

            if ($request->service_id || $request->referral_code) { // Recalculate prices if relevant fields change
                $service = ConsultationService::find($booking->service_id);
                if (!$service) {
                    throw new \Exception('Layanan konsultasi tidak ditemukan saat update.');
                }
                $totalPrice = $service->price;
                $discountAmount = 0;
                $referralCodeId = null;

                if ($request->referral_code) {
                    $referralCode = ReferralCode::where('code', $request->referral_code)->first();
                    if ($referralCode) {
                        $discountAmount = $totalPrice * ($referralCode->discount_percentage / 100);
                        $referralCodeId = $referralCode->id;
                    }
                }
                $booking->discount_amount = $discountAmount;
                $booking->referral_code_id = $referralCodeId;
                $booking->final_price = $totalPrice - $discountAmount;

                if ($booking->invoice) {
                    $amountToPay = $booking->payment_type === 'dp' ? $booking->final_price / 2 : $booking->final_price;
                    $booking->invoice->total_amount = $amountToPay;
                    $booking->invoice->save();
                }
            }
            $booking->save();

            DB::commit();

            return response()->json([
                'message' => 'Booking berhasil diperbarui.',
                'booking' => $booking->load(['user', 'service', 'invoice']),
                'invoice' => $booking->invoice
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation Error (Booking update): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating booking: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal memperbarui booking: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus booking.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $booking = ConsultationBooking::with('invoice')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
        }

        try {
            DB::beginTransaction();

            if ($booking->invoice) {
                $booking->invoice->delete();
            }
            $booking->delete();

            DB::commit();

            return response()->json(['message' => 'Booking berhasil dihapus.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting booking: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal menghapus booking: ' . $e->getMessage()], 500);
        }
    }
}
