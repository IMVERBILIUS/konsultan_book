<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Subheading;
use App\Models\Paragraph;
use App\Models\User; // Pastikan model User diimpor
use Illuminate\Support\Str; // Untuk fungsi Str::slug() dan Str::random()
use Illuminate\Support\Facades\Storage; // Untuk Storage::disk('public')
use Illuminate\Support\Facades\DB; // Untuk transaksi database
use Illuminate\Validation\ValidationException; // Untuk menangani error validasi
use Illuminate\Support\Facades\Log; // Impor Log facade

class ArticleController extends Controller
{
    /**
     * Tampilkan semua artikel (untuk daftar admin).
     * Mengembalikan data paginasi standar Laravel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $articles = Article::orderBy('created_at', 'desc')->paginate(10); // Pastikan ini menggunakan paginate()

            // Log debug untuk melihat format respons
            Log::info('DEBUG LARAVEL CONTROLLER [ARTICLE INDEX]: Pagination data sent: ' . json_encode($articles->toArray()));

            return response()->json($articles); // Langsung kembalikan objek paginator
        } catch (\Exception $e) {
            Log::error('Error fetching articles for admin list: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to fetch articles list.'], 500);
        }
    }

    /**
     * Simpan artikel baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // --- DEBUGGING: Log isi request di awal controller lifecycle ---
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Incoming request for store.');
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request headers: ' . json_encode($request->headers->all()));
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request all() before validation: ' . json_encode($request->all()));
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request has("title"): ' . ($request->has('title') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('title'));
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request has("slug"): ' . ($request->has('slug') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('slug'));
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request has("author"): ' . ($request->has('author') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('author'));
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request has("description"): ' . ($request->has('description') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('description'));
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request has("status"): ' . ($request->has('status') ? 'TRUE' : 'FALSE') . ' Value: ' . $request->input('status'));
        Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request hasFile("article_thumbnail_file"): ' . ($request->hasFile('article_thumbnail_file') ? 'TRUE' : 'FALSE'));
        if ($request->hasFile('article_thumbnail_file')) {
            $file = $request->file('article_thumbnail_file');
            Log::info('DEBUG LARAVEL CONTROLLER [STORE]: File details (hasFile is TRUE): OriginalName=' . $file->getClientOriginalName() . ', MimeType=' . $file->getMimeType() . ', Size=' . $file->getSize());
        } else {
            Log::info('DEBUG LARAVEL CONTROLLER [STORE]: Request hasFile is FALSE for article_thumbnail_file. File might not have reached PHP or name mismatch.');
        }
        // --- AKHIR DEBUGGING ---

        try {
            // Validasi input form
            $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:articles,slug|max:255',
                'description' => 'nullable|string',
                'article_thumbnail_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // Max 5MB = 5120KB
                'status' => 'required|in:Draft,Published',
                'author' => 'required|string|max:255',
                'subheadings' => 'nullable|json',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error (store): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        $thumbnailPath = null;
        if ($request->hasFile('article_thumbnail_file')) {
            $file = $request->file('article_thumbnail_file');
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/thumbnails', $fileName);
            $thumbnailPath = 'thumbnails/' . $fileName;
            \Log::info('DEBUG LARAVEL: Thumbnail uploaded (store): ' . $thumbnailPath);
        } else {
            \Log::info('DEBUG LARAVEL: No thumbnail file received in store request (after successful validation). This should not happen if validation requires it.');
        }

        try {
            DB::beginTransaction();

            $article = new Article();
            $article->user_id = $request->user()->id;
            $article->title = $request->title;
            $article->slug = Str::slug($request->slug);
            $article->author = $request->author;
            $article->description = $request->description;
            $article->thumbnail = $thumbnailPath;
            $article->status = $request->status;
            $article->views = 0;
            $article->published_at = ($request->status === 'Published') ? now() : null;
            $article->save();

            $subheadingsData = json_decode($request->subheadings, true);
            if ($subheadingsData && is_array($subheadingsData)) {
                foreach ($subheadingsData as $subIndex => $subheading) {
                    if (empty($subheading['title'])) {
                        throw new \Exception('Judul subheading tidak boleh kosong.');
                    }
                    $savedSub = $article->subheadings()->create([
                        'title' => $subheading['title'],
                        'order_number' => $subIndex + 1,
                    ]);

                    if (isset($subheading['paragraphs']) && is_array($subheading['paragraphs'])) {
                        foreach ($subheading['paragraphs'] as $paraIndex => $paragraph) {
                            if (empty($paragraph['content'])) {
                                throw new \Exception('Konten paragraf tidak boleh kosong.');
                            }
                            $savedSub->paragraphs()->create([
                                'content' => $paragraph['content'],
                                'order_number' => $paraIndex + 1,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Article created successfully!',
                'article' => $article->load('subheadings.paragraphs')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            \Log::error('Error creating article: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to create article: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tampilkan satu artikel (untuk edit di admin).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $article = Article::with('subheadings.paragraphs')->find($id);
        if (!$article) {
            return response()->json(['message' => 'Article not found.'], 404);
        }
        return response()->json($article);
    }

    /**
     * Perbarui artikel yang sudah ada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // --- DEBUGGING: Log isi request di awal controller lifecycle ---
        \Log::info('DEBUG LARAVEL CONTROLLER [UPDATE]: Incoming update request for Article ID: ' . $id);
        \Log::info('DEBUG LARAVEL CONTROLLER [UPDATE]: Request all(): ' . json_encode($request->all()));
        \Log::info('DEBUG LARAVEL CONTROLLER [UPDATE]: Request hasFile("article_thumbnail_file"): ' . ($request->hasFile('article_thumbnail_file') ? 'TRUE' : 'FALSE'));
        if ($request->hasFile('article_thumbnail_file')) {
            $file = $request->file('article_thumbnail_file');
            \Log::info('DEBUG LARAVEL CONTROLLER [UPDATE]: File details (hasFile is TRUE): OriginalName=' . $file->getClientOriginalName() . ', MimeType=' . $file->getMimeType() . ', Size=' . $file->getSize());
        } else {
            \Log::info('DEBUG LARAVEL CONTROLLER [UPDATE]: Request hasFile is FALSE for article_thumbnail_file. File might not have reached PHP or name mismatch.');
        }
        // --- AKHIR DEBUGGING ---

        $article = Article::with('subheadings.paragraphs')->find($id);
        if (!$article) {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        try {
            // Validasi input form
            $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:articles,slug,' . $id,
                'description' => 'nullable|string',
                'article_thumbnail_file' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // Max 5MB
                'status' => 'required|in:Draft,Published',
                'author' => 'required|string|max:255',
                'subheadings' => 'nullable|json',
            ]);
        } catch (ValidationException $e) {
            \Log::error('Validation Error (update): ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }

        $oldThumbnailPath = $article->thumbnail;

        try {
            DB::beginTransaction();

            if ($request->hasFile('article_thumbnail_file')) {
                $file = $request->file('article_thumbnail_file');
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/thumbnails', $fileName);
                $article->thumbnail = 'thumbnails/' . $fileName;

                if ($oldThumbnailPath) {
                    Storage::disk('public')->delete($oldThumbnailPath);
                    \Log::info('DEBUG LARAVEL: Old thumbnail deleted (update): ' . $oldThumbnailPath);
                }
                \Log::info('DEBUG LARAVEL: New thumbnail uploaded (update): ' . $article->thumbnail);
            } else {
                \Log::info('DEBUG LARAVEL: No new thumbnail file received in update request. Keeping old one if exists.');
            }

            $article->title = $request->title;
            $article->slug = Str::slug($request->slug);
            $article->author = $request->author;
            $article->description = $request->description;
            $article->status = $request->status;
            $article->published_at = ($request->status === 'Published' && is_null($article->published_at)) ? now() : $article->published_at;
            $article->save();

            $article->subheadings()->delete();

            $subheadingsData = json_decode($request->subheadings, true);
            if ($subheadingsData && is_array($subheadingsData)) {
                foreach ($subheadingsData as $subIndex => $subheading) {
                    if (empty($subheading['title'])) {
                        throw new \Exception('Judul subheading tidak boleh kosong.');
                    }
                    $savedSub = $article->subheadings()->create([
                        'title' => $subheading['title'],
                        'order_number' => $subIndex + 1,
                    ]);

                    if (isset($subheading['paragraphs']) && is_array($subheading['paragraphs'])) {
                        foreach ($subheading['paragraphs'] as $paraIndex => $paragraph) {
                            if (empty($paragraph['content'])) {
                                throw new \Exception('Konten paragraf tidak boleh kosong.');
                            }
                            $savedPara = $savedSub->paragraphs()->create([
                                'content' => $paragraph['content'],
                                'order_number' => $paraIndex + 1,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Article updated successfully!', 'article' => $article->load('subheadings.paragraphs')]);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->hasFile('article_thumbnail_file')) {
                Storage::disk('public')->delete($article->thumbnail);
            }
            \Log::error('Error updating article: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to update article: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus artikel.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $article = Article::find($id);
        if (!$article) {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        try {
            DB::beginTransaction();

            if ($article->thumbnail) {
                Storage::disk('public')->delete($article->thumbnail);
                \Log::info('DEBUG LARAVEL: Thumbnail deleted: ' . $article->thumbnail);
            }

            $article->delete();

            DB::commit();

            return response()->json(['message' => 'Article deleted successfully.']);

        } catch (\Exception | ValidationException $e) {
            DB::rollBack();
            \Log::error('Error deleting article: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to delete article: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tampilkan semua artikel yang dipublikasikan (untuk daftar publik).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexPublic(Request $request)
    {
        $articles = Article::where('status', 'Published')
                        ->orderBy('created_at', 'desc')
                        ->select('id', 'title', 'slug', 'author', 'description', 'thumbnail', 'views', 'created_at')
                        ->paginate(10);
        return response()->json($articles);
    }

    /**
     * Tampilkan satu artikel publik berdasarkan slug.
     * Views akan ditambah di sini.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPublicBySlug($slug)
    {
        $article = Article::with('subheadings.paragraphs')
                        ->where('slug', $slug)
                        ->where('status', 'Published')
                        ->first();

        if (!$article) {
            $checkDraft = Article::where('slug', $slug)->first();
            if($checkDraft){
                return response()->json(['message' => 'Article is not published yet.'], 403);
            }
            return response()->json(['message' => 'Article not found.'], 404);
        }

        try {
            $article->increment('views');
            \Log::info('DEBUG LARAVEL: Views incremented for slug: ' . $slug . '. New views: ' . ($article->views));

            return response()->json($article);

        } catch (\Exception | ValidationException $e) {
            \Log::error('Error incrementing views for slug ' . $slug . ': ' . $e->getMessage());
            $statusCode = ($e instanceof ValidationException) ? 422 : 500;
            return response()->json(['message' => 'Error processing article: ' . $e->getMessage()], $statusCode);
        }
    }

    /**
     * Dapatkan statistik dashboard admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboardStats(Request $request)
    {
        $totalUsers = User::count();
        $totalArticles = Article::count();
        $publishedArticles = Article::where('status', 'Published')->count();
        $draftArticles = Article::where('status', 'Draft')->count();

        return response()->json([
            'totalUsers' => $totalUsers,
            'totalArticles' => $totalArticles,
            'publishedArticles' => $publishedArticles,
            'draftArticles' => $draftArticles,
        ]);
    }
}
