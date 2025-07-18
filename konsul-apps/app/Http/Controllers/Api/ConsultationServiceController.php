<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConsultationService;
use Illuminate\Validation\ValidationException;

class ConsultationServiceController extends Controller
{
    public function index()
    {
        $services = ConsultationService::all();
        return response()->json($services);
    }

    public function show($id)
    {
        $service = ConsultationService::find($id);
        if (!$service) {
            return response()->json(['message' => 'Layanan tidak ditemukan.'], 404);
        }
        return response()->json($service);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'short_description' => 'required|string|max:255',
                'product_description' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $service = ConsultationService::create($request->all());
        return response()->json(['message' => 'Layanan berhasil dibuat.', 'service' => $service], 201);
    }

    public function update(Request $request, $id)
    {
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
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $service->update($request->all());
        return response()->json(['message' => 'Layanan berhasil diperbarui.', 'service' => $service]);
    }

    public function destroy($id)
    {
        $service = ConsultationService::find($id);
        if (!$service) {
            return response()->json(['message' => 'Layanan tidak ditemukan.'], 404);
        }

        $service->delete();
        return response()->json(['message' => 'Layanan berhasil dihapus.']);
    }
}
