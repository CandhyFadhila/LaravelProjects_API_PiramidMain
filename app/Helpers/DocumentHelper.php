<?php

namespace App\Helpers;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use App\Helpers\StorageServerHelper;

class DocumentHelper
{
	public static function uploadDocuments(array $files): array
	{
		$documentIds = [];
		$uploadedFiles = StorageServerHelper::uploadToServer($files);

		if (is_array($uploadedFiles) && count($uploadedFiles) > 0) {
			foreach ($uploadedFiles as $uploadedFile) {
				if (is_array($uploadedFile) && isset($uploadedFile['file_id'])) {
					$document = Document::create([
						'uploaded_by'        => auth()->user()->id,
						'verified_by'        => 1,
						'file_id'            => $uploadedFile['file_id'],
						'file_name'          => $uploadedFile['filename'],
						'file_path'          => $uploadedFile['url'],
						'file_url'           => $uploadedFile['url'],
						'file_mime_type'     => $uploadedFile['mime_type'],
						'file_size'          => $uploadedFile['size'],
						'reason'             => null
					]);

					$documentIds[] = $document->id;
					Log::channel('helper_document')->info('| uploadDocuments | - Document uploaded successfully', [
						'file_name' => $uploadedFile['filename'],
						'file_size' => $uploadedFile['size'],
						'document_id' => $document->id
					]);
				} else {
					Log::channel('helper_document')->error('| uploadDocuments | - Failed to save document. No file_id in response.', [$uploadedFile]);
				}
			}
		} else {
			Log::channel('helper_document')->error('| uploadDocuments | - Invalid upload response format.', [$uploadedFiles]);
		}

		return $documentIds;
	}

	public static function deleteDocuments(array $documentIdsToDelete): void
	{
		$documents = Document::whereIn('id', $documentIdsToDelete)->get();
		$fileIds = $documents->pluck('file_id')->toArray();

		if (!empty($fileIds)) {
			StorageServerHelper::deleteFromServer($fileIds);
			Log::channel('helper_document')->info('| deleteDocuments | - Deleted documents from storage server.', [
				'file_ids' => $fileIds,
				'document_ids' => $documentIdsToDelete
			]);
		}

		Document::whereIn('id', $documentIdsToDelete)->delete();
	}

	public static function deleteDocumentsAndNullify(mixed $model, string $documentField = 'document_id'): void
	{
		$documentIds = is_array($model->{$documentField})
			? $model->{$documentField}
			: json_decode($model->{$documentField}, true);

		if (!empty($documentIds)) {
			$fileIds = Document::whereIn('id', $documentIds)->pluck('file_id')->toArray();

			$model->update([$documentField => null]);

			if (!empty($fileIds)) {
				StorageServerHelper::deleteFromServer($fileIds);

				Log::channel('helper_document')->info('| deleteDocumentsAndNullify | - Deleted documents from storage server.', [
					'file_ids' => $fileIds
				]);
			}

			Document::whereIn('id', $documentIds)->delete();
		}
	}
}
