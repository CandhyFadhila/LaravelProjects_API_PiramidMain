<?php

namespace App\Docs;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoint terkait autentikasi: login, logout, dan user-info"
 * )
 */
class LoginDocs
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login user dan dapatkan token",
     *     description="Melakukan login dengan email dan password untuk mendapatkan akses token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="studio.exium@gmail.com"),
     *             @OA\Property(property="password", type="string", example="superadmin123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login sukses, token dikembalikan"),
     *     @OA\Response(response=401, description="Email atau password salah")
     * )
     */
    public function loginDocsExample() {}

    /**
     * @OA\Get(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="Logout user dari sistem",
     *     description="Logout akan menghapus token akses aktif.",

     *     @OA\Response(response=200, description="Logout berhasil"),
     *     @OA\Response(response=401, description="Tidak ada sesi aktif")
     * )
     */
    public function logoutDocsExample() {}

    /**
     * @OA\Get(
     *     path="/api/user-info",
     *     tags={"Authentication"},
     *     summary="Get user info",
     *     description="Mengambil informasi user berdasarkan token.",

     *     @OA\Response(response=200, description="User ditemukan"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function userInfoDocsExample() {}
}
