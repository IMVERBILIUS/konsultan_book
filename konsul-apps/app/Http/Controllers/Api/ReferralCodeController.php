<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReferralCode;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ReferralCodeController extends Controller
{
    /**
     * Tampilkan semua kode referral (untuk daftar admin).
     */
    public function index(Request $request)
    {
        try {
            $referralCodes = ReferralCode::orderBy('created_at', 'desc')->paginate(10);
            return response()->json($referralCodes);
        } catch (\Exception $e) {
            Log::error('Error fetching referral codes: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to fetch referral codes.'], 500);
        }
    }

    /**
     * Simpan kode referral baru.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'code' => 'nullable|string|unique:referral_codes,code|max:255',
                'discount_percentage' => 'required|numeric|min:0|max:100',
                'valid_from' => 'nullable|date',
                'valid_until' => 'nullable|date|after_or_equal:valid_from',
                'max_uses' => 'nullable|integer|min:0',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (ReferralCode store): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        try {
            $referralCode = new ReferralCode();
            $referralCode->code = $request->code ?: Str::upper(Str::random(10));
            $referralCode->discount_percentage = $request->discount_percentage;
            $referralCode->valid_from = $request->valid_from;
            $referralCode->valid_until = $request->valid_until;
            $referralCode->max_uses = $request->max_uses;
            $referralCode->current_uses = 0;
            $referralCode->created_by = $request->user()->id;
            $referralCode->save();

            return response()->json([
                'message' => 'Kode referral berhasil dibuat!',
                'referral_code' => $referralCode
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating referral code: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal membuat kode referral: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tampilkan satu kode referral.
     * Ini dipanggil oleh ReferralEditPage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // --- DEBUGGING: Log pengambilan kode referral admin ---
        Log::info('DEBUG LARAVEL CONTROLLER [REFERRAL SHOW ADMIN]: Incoming request for show with ID: ' . $id);
        if (!is_numeric($id) || $id <= 0) {
            Log::warning('DEBUG LARAVEL CONTROLLER [REFERRAL SHOW ADMIN]: Invalid ID received: ' . $id);
            return response()->json(['message' => 'ID kode referral tidak valid.'], 400);
        }
        // --- AKHIR DEBUGGING ---

        $referralCode = ReferralCode::find($id);

        if (!$referralCode) {
            Log::warning('DEBUG LARAVEL CONTROLLER [REFERRAL SHOW ADMIN]: Referral Code ID ' . $id . ' not found in database.');
            return response()->json(['message' => 'Kode referral tidak ditemukan.'], 404);
        }

        Log::info('DEBUG LARAVEL CONTROLLER [REFERRAL SHOW ADMIN]: Referral Code ID ' . $id . ' found. Code: "' . $referralCode->code . '"');
        return response()->json($referralCode);
    }

    /**
     * Perbarui kode referral.
     */
    public function update(Request $request, $id)
    {
        $referralCode = ReferralCode::find($id);
        if (!$referralCode) {
            return response()->json(['message' => 'Kode referral tidak ditemukan.'], 404);
        }

        try {
            $request->validate([
                'code' => 'sometimes|required|string|unique:referral_codes,code,' . $id . '|max:255',
                'discount_percentage' => 'sometimes|required|numeric|min:0|max:100',
                'valid_from' => 'nullable|date',
                'valid_until' => 'nullable|date|after_or_equal:valid_from',
                'max_uses' => 'nullable|integer|min:0',
                'current_uses' => 'sometimes|required|integer|min:0',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (ReferralCode update): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        try {
            $referralCode->code = $request->code ?? $referralCode->code;
            $referralCode->discount_percentage = $request->discount_percentage ?? $referralCode->discount_percentage;
            $referralCode->valid_from = $request->valid_from ?? $referralCode->valid_from;
            $referralCode->valid_until = $request->valid_until ?? $referralCode->valid_until;
            $referralCode->max_uses = $request->max_uses ?? $referralCode->max_uses;
            $referralCode->current_uses = $request->current_uses ?? $referralCode->current_uses;
            $referralCode->save();

            return response()->json([
                'message' => 'Kode referral berhasil diperbarui!',
                'referral_code' => $referralCode
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating referral code: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal memperbarui kode referral: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus kode referral.
     */
    public function destroy($id)
    {
        $referralCode = ReferralCode::find($id);
        if (!$referralCode) {
            return response()->json(['message' => 'Kode referral tidak ditemukan.'], 404);
        }

        try {
            $referralCode->delete();
            return response()->json(['message' => 'Kode referral berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Error deleting referral code: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal menghapus kode referral: ' . $e->getMessage()], 500);
        }
    }
}
