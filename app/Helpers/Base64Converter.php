<?php

use App\Helpers\StorageServerHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Base64Converter
{
	public static function convertBase64ToUploadedFiles(array $base64Files): array
	{
		$uploadedFiles = [];

		foreach ($base64Files as $base64File) {
			// Pecah bagian metadata dan data
			if (preg_match('/^data:(.*?);base64,(.*)$/', $base64File, $matches)) {
				$mimeType = $matches[1];
				$base64Data = $matches[2];
				$extension = StorageServerHelper::getExtensionFromMimeType($mimeType);

				$filename = Str::random(40) . '.' . $extension;
				$filePath = sys_get_temp_dir() . '/' . $filename;

				// Decode dan simpan ke file sementara
				file_put_contents($filePath, base64_decode($base64Data));

				// Buat UploadedFile palsu
				$uploadedFile = new UploadedFile(
					$filePath,
					$filename,
					$mimeType,
					null,
					true // $testMode=true supaya bisa diterima di Laravel
				);

				$uploadedFiles[] = $uploadedFile;
			} else {
				Log::channel('helper_base64_converter')->warning("Format base64 tidak valid atau tidak lengkap: ", $base64File);
			}
		}

		return $uploadedFiles;
	}
}
