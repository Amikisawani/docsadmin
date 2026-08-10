<?php

namespace App\Application\Archives;

use App\Domains\Archives\Models\Archive;
use App\Domains\Archives\Models\ArchiveBox;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentHistory;
use App\Domains\Users\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ArchiveDocumentUseCase
{
    public function execute(array $input, User $actor): Archive
    {
        $document = Document::query()->where('is_deleted', false)->findOrFail($input['document_id']);


        $boxId = (string) Arr::get($input, 'archive_box_id', '');
        /** @var ArchiveBox $box */
        $box = $boxId !== '' ? ArchiveBox::query()->findOrFail($boxId) : null;

        // Guard: idempotence - if already archived, return existing archive
        $existing = Archive::query()
            ->where('document_id', $document->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return $existing->load(['document', 'archiveBox']);
        }

        $category = (string) Arr::get($input, 'category', $document->document_type);
        $conservationDuration = (string) Arr::get($input, 'conservation_duration', '');
        $notes = Arr::get($input, 'notes');

        // Minimal conservation_until handling (until policy/config is introduced)
        $conservationUntil = null;
        $duration = (int) Arr::get($input, 'conservation_until_days', 0);
        if ($duration > 0) {
            $conservationUntil = now()->addDays($duration);
        }

        $archive = Archive::create([
            'document_id' => $document->id,
            'archive_box_id' => $box?->id,
            'reference' => $document->reference,
            'category' => $category,
            'conservation_duration' => $conservationDuration !== '' ? $conservationDuration : null,
            'archived_at' => now(),
            'conservation_until' => $conservationUntil,
            'status' => 'active',
            'notes' => $notes,
            'archived_by' => $actor->id,
        ]);

        // GED: mark document as archived
        $document->update([
            'is_archived' => true,
            // keep status consistent with archive phase
            'status' => $document->status === 'signed' || $document->status === 'approved' ? 'archived' : $document->status,
        ]);

        // GED: QR generation (placeholder - we persist qr_code_path using hash)
        // Step 2 will later make it cryptographically anchored to final signed/archived artifact.
        $qrAnchor = hash('sha256', 'AdminFlow|qr|' . (string) $document->hash);

        // Deterministic QR path for public integrity verification anchoring.
        // NOTE: qr_anchor column might not exist yet in some DB schemas/tests.
        // We still anchor using qr_code_path, keeping backward compatibility.
        if (empty($document->qr_code_path)) {
            $qrPath = 'qr/' . $document->id . '/' . $qrAnchor . '.png';
            $document->update(['qr_code_path' => $qrPath]);
        }



        // Audit/journal (DocumentHistory)
        // Migration for document_histories uses: description + metadata (not 'details')
        DocumentHistory::create([
            'document_id' => $document->id,
            'user_id' => $actor->id,
            'action' => 'archived',
            'description' => 'Document archivé',
            'metadata' => [
                'archive_id' => $archive->id,
                'archive_box_id' => $archive->archive_box_id,
                'category' => $archive->category,
            ],
        ]);



        return $archive->load(['document', 'archiveBox']);
    }
}

