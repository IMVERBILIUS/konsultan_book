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

    /**
     * Tampilkan semua booking (untuk daftar admin).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $bookings = ConsultationBooking::with(['user', 'services', 'referralCode', 'invoice'])
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(10);
            return response()->json($bookings);
        } catch (\Exception | ValidationException $e) {
            Log::error('Error fetching consultation bookings: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            $statusCode = ($e instanceof ValidationException) ? 422 : 500;
            return response()->json(['message' => 'Failed to fetch consultation bookings.'], $statusCode);
        }
    }

    /**
     * Tampilkan satu booking (show standar apiResource).
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

        $booking = ConsultationBooking::with(['user', 'services', 'referralCode', 'invoice'])->find($id);
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
        \Log::info('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Incoming request for showInvoice with Booking ID: ' . $bookingId);
        if (!is_numeric($bookingId) || $bookingId <= 0) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Invalid Booking ID received: ' . $bookingId);
            return response()->json(['message' => 'ID booking tidak valid.'], 400);
        }

        // --- PERBAIKAN: Muat relasi 'services' dan 'referralCode' langsung ke objek booking ---
        // Ini memastikan booking_details memiliki semua data yang diperlukan
        $booking = ConsultationBooking::with(['invoice.user', 'services', 'user', 'referralCode'])->find($bookingId);
        // --- AKHIR PERBAIKAN ---

        if (!$booking) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Booking ID ' . $bookingId . ' not found for invoice view.');
            return response()->json(['message' => 'Booking tidak ditemukan untuk invoice ini.'], 404);
        }

        if (!$booking->invoice) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Invoice not found for Booking ID: ' . $bookingId);
            return response()->json(['message' => 'Invoice tidak ditemukan untuk booking ini.'], 404);
        }

        \Log::info('DEBUG LARAVEL CONTROLLER [BOOKING SHOW INVOICE]: Invoice found for Booking ID ' . $bookingId . '. Invoice No: ' . $booking->invoice->invoice_no);

        // --- PERBAIKAN: Kembalikan objek booking details dan invoice secara terpisah ---
        return response()->json([
            'message' => 'Invoice berhasil ditemukan.',
            'booking_details' => $booking, // Ini adalah objek booking lengkap dengan relasi services, referralCode
            'invoice' => $booking->invoice->load('user') // Invoice juga dikembalikan terpisah, meload user nya
        ]);
        // --- AKHIR PERBAIKAN ---
    }

    /**
     * Simpan booking baru dan buat invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('DEBUG LARAVEL CONTROLLER [BOOKING STORE]: Incoming request for store.');
        Log::info('DEBUG LARAVEL CONTROLLER [BOOKING STORE]: Request all() before validation: ' . json_encode($request->all()));

        try {
            $request->validate([
                'selected_services' => 'required|array|min:1',
                'selected_services.*.service_id' => 'required|numeric|exists:consultation_services,id',
                'selected_services.*.booked_date' => 'required|date|after_or_equal:tomorrow', // Tanggal harus H+1 atau setelahnya
                'selected_services.*.booked_time' => 'required|date_format:H:i',
                'selected_services.*.session_type' => 'required|in:online,offline',
                'selected_services.*.offline_address' => 'nullable|string|required_if:selected_services.*.session_type,offline',

                'receiver_name' => 'nullable|string|max:255',
                'contact_preference' => 'required|in:chat_only,chat_and_call',
                'referral_code' => 'nullable|string|exists:referral_codes,code',
                'payment_type' => 'required|in:dp,full_payment',
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation Error (Booking store): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating booking: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal membuat booking: ' . $e->getMessage()], 500);
        }

        try {
            DB::beginTransaction();

            $selectedServicesData = $request->selected_services;

            $serviceDateTimeTypeCombinations = collect($selectedServicesData)->map(function ($item) {
                $serviceId = $item['service_id'] ?? 'null';
                $bookedDate = $item['booked_date'] ?? 'null';
                $bookedTime = $item['booked_time'] ?? 'null';
                $sessionType = $item['session_type'] ?? 'null';
                return $serviceId . '_' . $bookedDate . '_' . $bookedTime . '_' . $sessionType;
            })->toArray();

            if (count($serviceDateTimeTypeCombinations) !== count(array_unique($serviceDateTimeTypeCombinations))) {
                throw new \Exception('Satu layanan hanya dapat dipesan satu kali untuk kombinasi tanggal, waktu, dan tipe sesi yang unik dalam satu booking.');
            }

            $serviceIds = collect($selectedServicesData)->pluck('service_id')->filter()->toArray();
            $availableServices = ConsultationService::whereIn('id', $serviceIds)->get()->keyBy('id');
            if ($availableServices->count() !== count($serviceIds)) {
                throw new \Exception('Satu atau lebih layanan konsultasi tidak valid.');
            }

            $totalPriceSum = 0;
            $pivotData = [];

            foreach ($selectedServicesData as $serviceItem) {
                if (!isset($serviceItem['service_id']) || empty($serviceItem['service_id'])) {
                    continue;
                }
                $service = $availableServices->get($serviceItem['service_id']);
                if ($service) {
                    $totalPriceSum += $service->price;
                    $pivotData[$service->id] = [
                        'price_at_booking' => $service->price,
                        'booked_date' => $serviceItem['booked_date'],
                        'booked_time' => $serviceItem['booked_time'],
                        'session_type' => $serviceItem['session_type'],
                        'offline_address' => $serviceItem['session_type'] === 'offline' ? ($serviceItem['offline_address'] ?? self::OFFLINE_ADDRESS_DEFAULT) : null,
                    ];
                }
            }

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
                    $discountPercentage = $referralCode->discount_percentage;
                    $discountAmount = $totalPriceSum * ($discountPercentage / 100);
                    $referralCodeId = $referralCode->id;
                    $referralCode->increment('current_uses');
                    Log::info('DEBUG LARAVEL: Referral code ' . $referralCode->code . ' applied. Discount: ' . $discountAmount);
                } else {
                    Log::warning('DEBUG LARAVEL: Invalid or expired referral code: ' . $request->referral_code);
                }
            }

            $finalPrice = $totalPriceSum - $discountAmount;
            $amountToPay = $request->payment_type === 'dp' ? $finalPrice / 2 : $finalPrice;

            $invoice = new Invoice();
            $invoice->user_id = $request->user()->id; // User yang membuat booking, bukan penerima
            $invoice->invoice_no = 'INV-' . Str::upper(Str::random(8)) . '-' . now()->format('Ymd');
            $invoice->invoice_date = now();
            $invoice->due_date = now()->addDay();
            $invoice->total_amount = $amountToPay;
            $invoice->payment_type = $request->payment_type;
            $invoice->payment_status = 'unpaid';
            $invoice->session_type = $selectedServicesData[0]['session_type'] ?? 'online'; // Ambil dari layanan pertama
            $invoice->save();
            Log::info('DEBUG LARAVEL: Invoice created: ' . $invoice->invoice_no . ' for amount: ' . $invoice->total_amount);

            $booking = ConsultationBooking::create([
                'user_id' => $request->user()->id, // User yang membuat booking (dari token)
                'receiver_name' => $request->receiver_name,
                'referral_code_id' => $referralCodeId,
                'invoice_id' => $invoice->id,
                'contact_preference' => $request->contact_preference,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'payment_type' => $request->payment_type,
                'session_status' => 'menunggu pembayaran',
            ]);
            Log::info('DEBUG LARAVEL: Booking created. Booking ID: ' . $booking->id);

            $booking->services()->attach($pivotData);

            DB::commit();

            return response()->json([
                'message' => 'Booking berhasil dibuat!',
                'booking' => $booking->load(['user', 'services', 'invoice']),
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
                'selected_services' => 'sometimes|required|array|min:1',
                'selected_services.*.service_id' => 'required|exists:consultation_services,id',
                'selected_services.*.booked_date' => 'required|date|after_or_equal:today',
                'selected_services.*.booked_time' => 'required|date_format:H:i',
                'selected_services.*.session_type' => 'required|in:online,offline',
                'selected_services.*.offline_address' => 'nullable|string|required_if:selected_services.*.session_type,offline',
                'receiver_name' => 'sometimes|required|string|max:255',
                'contact_preference' => 'sometimes|required|in:chat_only,chat_and_call',
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

            $booking->contact_preference = $request->contact_preference ?? $booking->contact_preference;
            $booking->payment_type = $request->payment_type ?? $booking->payment_type;
            $booking->session_status = $request->session_status ?? $booking->session_status;
            $booking->receiver_name = $request->receiver_name ?? $booking->receiver_name;

            $newServiceIdsData = $request->selected_services ?? [];

            $serviceDateTimeTypeCombinations = collect($newServiceIdsData)->map(function ($item) {
                $serviceId = $item['service_id'] ?? 'null';
                $bookedDate = $item['booked_date'] ?? 'null';
                $bookedTime = $item['booked_time'] ?? 'null';
                $sessionType = $item['session_type'] ?? 'null';
                return $serviceId . '_' . $bookedDate . '_' . $bookedTime . '_' . $sessionType;
            })->toArray();

            if (count($serviceDateTimeTypeCombinations) !== count(array_unique($serviceDateTimeTypeCombinations))) {
                throw new \Exception('Satu layanan hanya dapat dipesan satu kali untuk kombinasi tanggal, waktu, dan tipe sesi yang unik dalam satu booking (saat update).');
            }

            $newSelectedServices = ConsultationService::whereIn('id', collect($newServiceIdsData)->pluck('service_id'))->get()->keyBy('id');
            if (collect($newServiceIdsData)->count() !== $newSelectedServices->count()) {
                throw new \Exception('Satu atau lebih layanan konsultasi baru tidak valid saat update.');
            }

            $pivotData = collect($newServiceIdsData)->mapWithKeys(function ($serviceItem) use ($newSelectedServices) {
                $service = $newSelectedServices->get($serviceItem['service_id']);
                return [$service->id => [
                    'price_at_booking' => $service->price,
                    'booked_date' => $serviceItem['booked_date'],
                    'booked_time' => $serviceItem['booked_time'],
                    'session_type' => $serviceItem['session_type'],
                    'offline_address' => $serviceItem['session_type'] === 'offline' ? ($serviceItem['offline_address'] ?? self::OFFLINE_ADDRESS_DEFAULT) : null,
                ]];
            })->toArray();
            $booking->services()->sync($pivotData);

            $totalPrice = $booking->services()->sum('price_at_booking');
            $discountAmount = 0;
            $referralCodeId = $booking->referral_code_id;

            if ($request->referral_code) {
                $referralCode = ReferralCode::where('code', $request->referral_code)->first();
                if ($referralCode) {
                    $discountAmount = $totalPrice * ($referralCode->discount_percentage / 100);
                    $referralCodeId = $referralCode->id;
                } else {
                    $discountAmount = 0;
                    $referralCodeId = null;
                }
            } else {
                $discountAmount = 0;
                $referralCodeId = null;
            }

            $booking->discount_amount = $discountAmount;
            $booking->referral_code_id = $referralCodeId;
            $booking->final_price = $totalPrice - $discountAmount;

            $booking->session_type = $newServiceIdsData[0]['session_type'] ?? 'online'; // Ambil dari layanan pertama (untuk invoice)

            if ($booking->invoice) {
                $amountToPay = $booking->payment_type === 'dp' ? $booking->final_price / 2 : $booking->final_price;
                $booking->invoice->total_amount = $amountToPay;
                $booking->invoice->save();
            }
            $booking->save();

            DB::commit();

            return response()->json([
                'message' => 'Booking berhasil diperbarui.',
                'booking' => $booking->load(['user', 'services', 'invoice']),
                'invoice' => $booking->invoice
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation Error (Booking update): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception | ValidationException $e) { // Tangani ValidationException juga
            DB::rollBack();
            Log::error('Error updating booking: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            $statusCode = ($e instanceof ValidationException) ? 422 : 500;
            return response()->json(['message' => 'Gagal memperbarui booking: ' . $e->getMessage()], $statusCode);
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

            $booking->services()->detach(); // Detach layanan dari tabel pivot sebelum menghapus booking

            if ($booking->invoice) {
                $booking->invoice->delete();
            }
            $booking->delete();

            DB::commit();

            return response()->json(['message' => 'Booking berhasil dihapus.']);

        } catch (\Exception | ValidationException $e) {
            DB::rollBack();
            Log::error('Error deleting booking: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            $statusCode = ($e instanceof ValidationException) ? 422 : 500;
            return response()->json(['message' => 'Gagal menghapus booking: ' . $e->getMessage()], $statusCode);
        }
    }
}
