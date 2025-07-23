<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConsultationService; // Pastikan model ini diimpor
use Illuminate\Support\Str; // Untuk random string
use Illuminate\Support\Facades\Storage; // Untuk upload file
use Illuminate\Validation\ValidationException; // Untuk validasi
use Illuminate\Support\Facades\Log; // Untuk logging

class ConsultationServiceController extends Controller
{
    /**
     * Tampilkan semua layanan konseling.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $services = ConsultationService::orderBy('created_at', 'desc')->paginate(10);
            return response()->json($services);
        } catch (\Exception $e) {
            Log::error('Error fetching consultation services: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to fetch consultation services.'], 500);
        }
    }

    /**
     * Tampilkan satu layanan konseling berdasarkan ID.
     * Ini dipanggil oleh ConsultationServiceEditPage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // --- DEBUGGING: Log pengambilan layanan admin ---
        Log::info('DEBUG LARAVEL CONTROLLER [CONSULTATION SERVICE SHOW ADMIN]: Incoming request for show with ID: ' . $id);
        // Pastikan $id bukan null atau non-numerik sebelum mencari
        if (!is_numeric($id) || $id <= 0) {
            Log::warning('DEBUG LARAVEL CONTROLLER [CONSULTATION SERVICE SHOW ADMIN]: Invalid ID received: ' . $id);
            return response()->json(['message' => 'ID layanan tidak valid.'], 400);
        }
        // --- AKHIR DEBUGGING ---

        $service = ConsultationService::find($id); // Mencari berdasarkan ID

        if (!$service) {
            Log::warning('DEBUG LARAVEL CONTROLLER [CONSULTATION SERVICE SHOW ADMIN]: Service ID ' . $id . ' not found in database.');
            return response()->json(['message' => 'Layanan tidak ditemukan.'], 404);
        }

        Log::info('DEBUG LARAVEL CONTROLLER [CONSULTATION SERVICE SHOW ADMIN]: Service ID ' . $id . ' found. Title: "' . $service->title . '"');
        return response()->json($service);
    }

    /**
     * Simpan layanan konseling baru (Hanya untuk Admin).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // --- DEBUGGING MENDALAM: Cek Request Input & Files ---
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Incoming request for store.');
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request headers: ' . json_encode($request->headers->all()));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request all() before validation: ' . json_encode($request->all())); // INI KUNCI!
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request has("title"): ' . ($request->has('title') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('title'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request has("price"): ' . ($request->has('price') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('price'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request has("short_description"): ' . ($request->has('short_description') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('short_description'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request has("product_description"): ' . ($request->has('product_description') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('product_description'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request hasFile("service_thumbnail_file"): ' . ($request->hasFile('service_thumbnail_file') ? 'TRUE' : 'FALSE'));
        if ($request->hasFile('service_thumbnail_file')) {
            $file = $request->file('service_thumbnail_file');
            Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: File details (hasFile is TRUE): OriginalName=' . $file->getClientOriginalName() . ', MimeType=' . $file->getMimeType() . ', Size=' . $file->getSize());
        } else {
            Log::info('DEBUG LARAVEL CONTROLLER [SERVICE STORE]: Request hasFile is FALSE for service_thumbnail_file. File might not have reached PHP or name mismatch.');
        }
        // --- AKHIR DEBUGGING ---

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'short_description' => 'required|string|max:255',
                'product_description' => 'required|string',
                'service_thumbnail_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // Max 5MB
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (ConsultationService store): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        $thumbnailPath = null;
        if ($request->hasFile('service_thumbnail_file')) {
            $file = $request->file('service_thumbnail_file');
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/thumbnails/services', $fileName);
            $thumbnailPath = 'thumbnails/services/' . $fileName;
            Log::info('DEBUG LARAVEL: Service thumbnail uploaded (store): ' . $thumbnailPath);
        } else {
            Log::info('DEBUG LARAVEL: No service thumbnail file received in store request (after validation).');
        }

        try {
            $service = ConsultationService::create([
                'title' => $request->title,
                'price' => $request->price,
                'short_description' => $request->short_description,
                'product_description' => $request->product_description,
                'thumbnail' => $thumbnailPath,
            ]);
            return response()->json(['message' => 'Layanan berhasil dibuat.', 'service' => $service], 201);
        } catch (\Exception $e) {
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            Log::error('Error creating consultation service: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal membuat layanan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Perbarui layanan konseling (Hanya untuk Admin).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // --- DEBUGGING MENDALAM: Cek Request Input & Files ---
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Incoming update request for Service ID: ' . $id);
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Request headers: ' . json_encode($request->headers->all()));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Request all() before validation: ' . json_encode($request->all()));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Request has("title"): ' . ($request->has('title') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('title'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Request has("price"): ' . ($request->has('price') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('price'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Request has("short_description"): ' . ($request->has('short_description') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('short_description'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Request has("product_description"): ' . ($request->has('product_description') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('product_description'));
        Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: Request hasFile("service_thumbnail_file"): ' . ($request->hasFile('service_thumbnail_file') ? 'TRUE' : 'FALSE'));
        if ($request->hasFile('service_thumbnail_file')) {
            $file = $request->file('service_thumbnail_file');
            Log::info('DEBUG LARAVEL CONTROLLER [SERVICE UPDATE]: File details (hasFile is TRUE): OriginalName=' . $file->getClientOriginalName() . ', MimeType=' . $file->getMimeType() . ', Size=' . $file->getSize());
        }
        // --- AKHIR DEBUGGING ---

        $service = ConsultationService::find($id);
        if (!$service) {
            return response()->json(['message' => 'Layanan tidak ditemukan.'], 404);
        }

        try {
            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|numeric|min:0',
                'short_description' => 'sometimes|required|string|max:255',
                'product_description' => 'sometimes|required|string',
                'service_thumbnail_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // Max 5MB
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (ConsultationService update): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        $oldThumbnailPath = $service->thumbnail;

        try {
            if ($request->hasFile('service_thumbnail_file')) {
                $file = $request->file('service_thumbnail_file');
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/thumbnails/services', $fileName);
                $service->thumbnail = 'thumbnails/services/' . $fileName;

                if ($oldThumbnailPath) {
                    Storage::disk('public')->delete($oldThumbnailPath);
                    \Log::info('DEBUG LARAVEL: Old service thumbnail deleted (update): ' . $oldThumbnailPath);
                }
                \Log::info('DEBUG LARAVEL: New service thumbnail uploaded (update): ' . $service->thumbnail);
            } else {
                \Log::info('DEBUG LARAVEL: No new service thumbnail file received in update request. Keeping old one if exists.');
            }

            $service->title = $request->title ?? $service->title;
            $service->price = $request->price ?? $service->price;
            $service->short_description = $request->short_description ?? $service->short_description;
            $service->product_description = $request->product_description ?? $service->product_description;
            $service->save();

            return response()->json(['message' => 'Layanan berhasil diperbarui.', 'service' => $service]);

        } catch (\Exception $e) {
            if ($request->hasFile('service_thumbnail_file')) {
                Storage::disk('public')->delete($service->thumbnail);
            }
            \Log::error('Error updating consultation service: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal memperbarui layanan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus layanan konseling (Hanya untuk Admin).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $service = ConsultationService::find($id);
        if (!$service) {
            return response()->json(['message' => 'Layanan tidak ditemukan.'], 404);
        }

        try {
            if ($service->thumbnail) {
                Storage::disk('public')->delete($service->thumbnail);
                \Log::info('DEBUG LARAVEL: Service thumbnail deleted: ' . $service->thumbnail);
            }
            $service->delete();
            return response()->json(['message' => 'Layanan berhasil dihapus.']);
        } catch (\Exception $e) {
            \Log::error('Error deleting consultation service: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal menghapus layanan: ' . $e->getMessage()], 500);
        }
    }
}
