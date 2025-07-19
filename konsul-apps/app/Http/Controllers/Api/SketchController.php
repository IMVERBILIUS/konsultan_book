<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sketch;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SketchController extends Controller
{
    /**
     * Tampilkan semua sketsa (untuk daftar admin).
     */
    public function index(Request $request)
    {
        try {
            $sketches = Sketch::orderBy('created_at', 'desc')->paginate(10);
            return response()->json($sketches);
        } catch (\Exception $e) {
            Log::error('Error fetching sketches for admin list: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to fetch sketches list.'], 500);
        }
    }

    /**
     * Simpan sketsa baru.
     */
    public function store(Request $request)
    {
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Incoming request for store.');
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request headers: ' . json_encode($request->headers->all()));
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request all() before validation: ' . json_encode($request->all()));
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request has("title"): ' . ($request->has('title') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('title'));
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request has("slug"): ' . ($request->has('slug') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('slug'));
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request has("author"): ' . ($request->has('author') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('author'));
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request has("content"): ' . ($request->has('content') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('content'));
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request has("status"): ' . ($request->has('status') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('status'));
        Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request file() existence for "sketch_thumbnail_file": ' . ($request->hasFile('sketch_thumbnail_file') ? 'TRUE' : 'FALSE'));
        if ($request->hasFile('sketch_thumbnail_file')) {
            $file = $request->file('sketch_thumbnail_file');
            Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: File details (hasFile is TRUE): OriginalName=' . $file->getClientOriginalName() . ', MimeType=' . $file->getMimeType() . ', Size=' . $file->getSize());
        } else {
            Log::info('DEBUG LARAVEL CONTROLLER [SKETCH STORE]: Request hasFile is FALSE for sketch_thumbnail_file. File might not have reached PHP or name mismatch.');
        }

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:sketches,slug|max:255',
                'author' => 'required|string|max:255',
                'content' => 'required|string',
                'sketch_thumbnail_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
                'status' => 'required|in:Draft,Published',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (Sketch store): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        $thumbnailPath = null;
        if ($request->hasFile('sketch_thumbnail_file')) {
            $file = $request->file('sketch_thumbnail_file');
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/thumbnails', $fileName);
            $thumbnailPath = 'thumbnails/' . $fileName;
            Log::info('DEBUG LARAVEL: Sketch thumbnail uploaded (store): ' . $thumbnailPath);
        } else {
            Log::info('DEBUG LARAVEL: No thumbnail file received in store request (after validation).');
        }

        try {
            $sketch = new Sketch();
            $sketch->user_id = $request->user()->id;
            $sketch->title = $request->title;
            $sketch->slug = Str::slug($request->slug);
            $sketch->author = $request->author;
            $sketch->content = $request->content;
            $sketch->thumbnail = $thumbnailPath;
            $sketch->status = $request->status;
            $sketch->views = 0;
            $sketch->save();

            return response()->json([
                'message' => 'Sketch created successfully!',
                'sketch' => $sketch
            ], 201);

        } catch (\Exception $e) {
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            Log::error('Error creating sketch: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to create sketch: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tampilkan satu sketsa (untuk edit di admin).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id) // Ini dipanggil oleh AdminSketchDetailPage dan SketchEditPage
    {
        // --- DEBUGGING: Log pengambilan sketsa admin ---
        \Log::info('DEBUG LARAVEL CONTROLLER [SKETCH SHOW ADMIN]: Fetching sketch with ID: ' . $id);
        // --- AKHIR DEBUGGING ---

        // PENTING: Hanya panggil find(), jangan where('status', 'Published') di sini
        $sketch = Sketch::find($id); // Find by ID, terlepas dari status

        if (!$sketch) {
            \Log::warning('DEBUG LARAVEL CONTROLLER [SKETCH SHOW ADMIN]: Sketch ID ' . $id . ' not found for admin view.');
            return response()->json(['message' => 'Sketsa tidak ditemukan.'], 404);
        }

        \Log::info('DEBUG LARAVEL CONTROLLER [SKETCH SHOW ADMIN]: Sketch ID ' . $id . ' found. Status: ' . $sketch->status);
        return response()->json($sketch);
    }

    /**
     * Perbarui sketsa yang sudah ada.
     */
    public function update(Request $request, $id)
    {
        \Log::info('DEBUG LARAVEL CONTROLLER [SKETCH UPDATE]: Incoming update request for Sketch ID: ' . $id);
        \Log::info('DEBUG LARAVEL CONTROLLER [SKETCH UPDATE]: Request all() before validation: ' . json_encode($request->all()));
        \Log::info('DEBUG LARAVEL CONTROLLER [SKETCH UPDATE]: Request hasFile("sketch_thumbnail_file"): ' . ($request->hasFile('sketch_thumbnail_file') ? 'TRUE' : 'FALSE'));
        if ($request->hasFile('sketch_thumbnail_file')) {
            $file = $request->file('sketch_thumbnail_file');
            \Log::info('DEBUG LARAVEL CONTROLLER [SKETCH UPDATE]: File details (hasFile is TRUE): OriginalName=' . $file->getClientOriginalName() . ', MimeType=' . $file->getMimeType() . ', Size=' . $file->getSize());
        }

        $sketch = Sketch::find($id);
        if (!$sketch) {
            return response()->json(['message' => 'Sketch not found.'], 404);
        }

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:sketches,slug,' . $id,
                'author' => 'required|string|max:255',
                'content' => 'required|string',
                'sketch_thumbnail_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
                'status' => 'required|in:Draft,Published',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (Sketch update): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        $oldThumbnailPath = $sketch->thumbnail;

        try {
            if ($request->hasFile('sketch_thumbnail_file')) {
                $file = $request->file('sketch_thumbnail_file');
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/thumbnails', $fileName);
                $sketch->thumbnail = 'thumbnails/' . $fileName;

                if ($oldThumbnailPath) {
                    Storage::disk('public')->delete($oldThumbnailPath);
                    Log::info('DEBUG LARAVEL: Old sketch thumbnail deleted (update): ' . $oldThumbnailPath);
                }
                Log::info('DEBUG LARAVEL: New sketch thumbnail uploaded (update): ' . $sketch->thumbnail);
            } else {
                Log::info('DEBUG LARAVEL: No new sketch thumbnail file received in update request. Keeping old one if exists.');
            }

            $sketch->title = $request->title;
            $sketch->slug = Str::slug($request->slug);
            $sketch->author = $request->author;
            $sketch->content = $request->content;
            $sketch->status = $request->status;
            $sketch->save();

            return response()->json([
                'message' => 'Sketch updated successfully!',
                'sketch' => $sketch
            ]);

        } catch (\Exception $e) {
            if ($request->hasFile('sketch_thumbnail_file')) {
                Storage::disk('public')->delete($sketch->thumbnail);
            }
            Log::error('Error updating sketch: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to update sketch: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus sketsa.
     */
    public function destroy($id)
    {
        $sketch = Sketch::find($id);
        if (!$sketch) {
            return response()->json(['message' => 'Sketch not found.'], 404);
        }

        try {
            if ($sketch->thumbnail) {
                Storage::disk('public')->delete($sketch->thumbnail);
                Log::info('DEBUG LARAVEL: Sketch thumbnail deleted: ' . $sketch->thumbnail);
            }
            $sketch->delete();
            return response()->json(['message' => 'Sketch deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Error deleting sketch: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to delete sketch: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tampilkan semua sketsa yang dipublikasikan (untuk daftar publik).
     */
    public function indexPublic(Request $request)
    {
        $sketches = Sketch::where('status', 'Published')
                        ->orderBy('created_at', 'desc')
                        ->select('id', 'title', 'slug', 'author', 'thumbnail', 'views', 'content', 'created_at')
                        ->paginate(10);
        return response()->json($sketches);
    }

    /**
     * Tampilkan satu sketsa publik berdasarkan slug.
     * Views akan ditambah di sini.
     */
    public function showPublicBySlug($slug)
    {
        // --- PENTING: Metode ini TETAP memfilter berdasarkan 'Published' dan menambah views ---
        $sketch = Sketch::where('slug', $slug)
                        ->where('status', 'Published')
                        ->first();

        if (!$sketch) {
            $checkDraft = Sketch::where('slug', $slug)->first();
            if($checkDraft){
                return response()->json(['message' => 'Sketch is not published yet.'], 403);
            }
            return response()->json(['message' => 'Sketch not found.'], 404);
        }

        try {
            $sketch->increment('views');
            Log::info('DEBUG LARAVEL: Views incremented for sketch slug: ' . $slug . '. New views: ' . ($sketch->views));
            return response()->json($sketch);
        } catch (\Exception $e) {
            Log::error('Error incrementing views for sketch slug ' . $slug . ': ' . $e->getMessage());
            return response()->json(['message' => 'Error processing sketch: ' . $e->getMessage()], 500);
        }
    }
}
