<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:reader,author,admin',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'reader',
        ]);

        // --- PERUBAHAN: Mengeluarkan token Sanctum ---
        $token = $user->createToken('auth_token')->plainTextToken; // Token Sanctum
        // --- AKHIR PERUBAHAN ---

        return response()->json([
            'message' => 'Registration successful!',
            'user' => $user,
            'accessToken' => $token, // Tetap gunakan 'accessToken' agar frontend tidak perlu banyak berubah
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // --- PERUBAHAN: Menghapus token lama (jika ada) dan mengeluarkan token Sanctum baru ---
        // Sanctum tidak seperti Passport yang secara otomatis menghapus client/tokens.
        // Anda bisa menghapus token lama yang dimiliki user ini jika ingin hanya 1 token aktif.
        $user->tokens()->where('name', 'auth_token')->delete(); // Hapus token bernama 'auth_token'

        $token = $user->createToken('auth_token')->plainTextToken; // Token Sanctum
        // --- AKHIR PERUBAHAN ---

        return response()->json([
            'message' => 'Login successful!',
            'user' => $user,
            'accessToken' => $token, // Tetap gunakan 'accessToken'
        ]);
    }

    public function logout(Request $request)
    {
        // --- PERUBAHAN: Revoke token Sanctum saat ini ---
        $request->user()->currentAccessToken()->delete();
        // --- AKHIR PERUBAHAN ---

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
