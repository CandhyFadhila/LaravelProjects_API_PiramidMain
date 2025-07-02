<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class StorageServerHelper
{
	private static $token = null;
	private static $storageDomain = null;
	private static $storageUsername = null;
	private static $storagePassword = null;

	// Initialize storageDomain once
	private static function initDomain()
	{
		if (self::$storageDomain === null) {
			self::$storageDomain = env('DOMAIN_STORAGE');
			self::$storageUsername = env('USERNAME_STORAGE');
			self::$storagePassword = env('PASSWORD_STORAGE');
		}
	}

	public static function login()
	{
		self::initDomain(); // Ensure domain is initialized
		$response = Http::asForm()->post(self::$storageDomain . '/api/docs/login', [
			'email' => self::$storageUsername,
			'password' => self::$storagePassword
		]);

		$logininfo = $response->json();
		Log::info($logininfo);

		if ($response->failed() || !isset($logininfo['message']['data']['token'])) {
			Log::channel('helper_storage_server')->error('| login | -Failed to login to storage server', [
				'status_code' => $response->status(),
				'error_message' => $response->body()
			]);
		}

		self::$token = $logininfo['message']['data']['token'];
	}

	public static function logout()
	{
		if (self::$token) {
			self::initDomain();
			Http::withHeaders([
				'Authorization' => 'Bearer ' . self::$token,
			])->get(self::$storageDomain . '/api/docs/logout');

			self::$token = null;
		}
	}

	public static function uploadToServer($files)
	{
		self::login();
		self::initDomain();

		// Normalize single file to array
		if ($files instanceof \Illuminate\Http\UploadedFile) {
			$files = [$files];
		}

		if (!is_array($files) || empty($files)) {
			Log::channel('helper_storage_server')->error('| uploadToServer | - Multiple upload gagal! Tidak ada file yang dikirim.');
			throw new \Exception('Tidak ada file yang dikirim untuk diunggah.');
		}

		$multipartData = [];

		foreach ($files as $file) {
			if (!$file->isValid()) {
				Log::channel('helper_storage_server')->error('| uploadToServer | - File tidak valid: ' . $file->getClientOriginalName());
				continue;
			}

			$filename = Str::random(35) . '.' . $file->getClientOriginalExtension();
			$multipartData[] = [
				'name' => 'files[]', // Nama array harus sama dengan sid-dokumen
				'contents' => fopen($file->getRealPath(), 'r'),
				'filename' => $filename,
			];
		}

		if (empty($multipartData)) {
			Log::channel('helper_storage_server')->error('| uploadToServer | - Tidak ada file yang valid untuk diunggah.');
			throw new \Exception('Tidak ada file yang valid untuk diunggah.');
		}

		$responseupload = Http::withHeaders([
			'Authorization' => 'Bearer ' . self::$token,
		])->asMultipart()->post(self::$storageDomain . '/api/docs/upload-file-multiple', $multipartData);

		// Logging
		Log::channel('helper_storage_server')->info('| uploadToServer | - Storage Server Upload Multiple Response: ' . $responseupload->body());

		// Cek apakah HTTP gagal
		if ($responseupload->failed()) {
			Log::channel('helper_storage_server')->error('| uploadToServer | - Upload file ke storage server gagal.', [
				'status' => $responseupload->status(),
				'response' => $responseupload->body(),
			]);
			throw new \Exception('Gagal mengunggah file ke server penyimpanan.');
		}

		// Cek format JSON
		$uploadinfo = json_decode($responseupload->body(), true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			Log::channel('helper_storage_server')->error('| uploadToServer | - Gagal decode response JSON dari storage server.', [
				'body' => $responseupload->body(),
			]);
			throw new \Exception('Gagal membaca respon dari server penyimpanan.');
		}

		if (!isset($uploadinfo['message']['data'])) {
			Log::channel('helper_storage_server')->error('| uploadToServer | - Upload multiple files gagal! Response:', [$uploadinfo]);
			throw new \Exception('Gagal mengunggah file. Response tidak memiliki kunci "data".');
		}

		self::logout();

		return $uploadinfo['message']['data'];
	}

	// Delete Berkas
	public static function deleteFromServer(array $fileIds)
	{
		self::login();
		self::initDomain();

		if (empty($fileIds)) {
			Log::channel('helper_storage_server')->warning('| deleteFromServer | - Tidak ada file ID yang diberikan untuk dihapus.');
			throw new \Exception('Tidak ada file yang dikirim untuk dihapus.');
		}

		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . self::$token,
		])->post(self::$storageDomain . '/api/docs/delete-file-multiple', [
			'file_id' => $fileIds,
		]);

		$result = $response->json();
		Log::channel('helper_storage_server')->info('| deleteFromServer | - Response dari delete dokumen:', $result);

		if ($response->failed()) {
			Log::channel('helper_storage_server')->error('| deleteFromServer | - Gagal menghapus dokumen dari storage server.', [
				'status' => $response->status(),
				'response' => $response->body(),
			]);
			throw new \Exception('Gagal menghapus file dari storage server.');
		}

		self::logout();

		return $result['message']['data'];
	}

	public static function getExtensionFromMimeType($mimeType)
	{
		$mimeMap = [
			'text/plain' => 'txt',
			'text/html' => 'html',
			'text/css' => 'css',
			'text/csv' => 'csv',
			'text/xml' => 'xml',
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/bmp' => 'bmp',
			'image/webp' => 'webp',
			'image/svg+xml' => 'svg',
			'audio/mpeg' => 'mp3',
			'audio/ogg' => 'ogg',
			'audio/wav' => 'wav',
			'audio/x-ms-wma' => 'wma',
			'video/mp4' => 'mp4',
			'video/ogg' => 'ogv',
			'video/webm' => 'webm',
			'video/x-msvideo' => 'avi',
			'video/x-ms-wmv' => 'wmv',
			'application/pdf' => 'pdf',
			'application/zip' => 'zip',
			'application/x-rar-compressed' => 'rar',
			'application/vnd.ms-excel' => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
			'application/msword' => 'doc',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
			'application/vnd.ms-powerpoint' => 'ppt',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
			'application/json' => 'json',
			'application/javascript' => 'js',
			'application/vnd.oasis.opendocument.text' => 'odt',
			'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
			'application/vnd.oasis.opendocument.presentation' => 'odp',
			'font/otf' => 'otf',
			'font/ttf' => 'ttf',
			'font/woff' => 'woff',
			'font/woff2' => 'woff2',
			'application/octet-stream' => 'bin',
		];

		return $mimeMap[$mimeType] ?? 'bin';
	}
}
