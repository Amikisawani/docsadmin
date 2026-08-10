<?php

namespace App\Http\Controllers\Public;

use App\Domains\Documents\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PublicDocumentVerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $hash = $request->query('hash');

        if (! is_string($hash) || $hash === '') {
            return response()->json([
                'message' => 'Hash manquant ou invalide.',
            ], 422);
        }

        $document = Document::with(['author', 'department', 'signatures.signature', 'histories'])
            ->where('hash', $hash)
            ->first();

        if (! $document) {
            return response()->json([
                'is_valid' => false,
                'message' => 'Document introuvable pour le hash fourni.',
            ], 404);
        }

        // Validation minimale à ce stade :
        // - le document existe pour ce hash
        // - le hash stocké correspond à l'intégrité attendue
        // IMPORTANT : pour une GED juridiquement solide, on devra recalculer le hash à partir d'un
        //       contenu stable (ex: rendu PDF final signé) et comparer avec le hash enregistré.
        $expectedHash = hash('sha256', ($document->content ?? '').($document->document_date ? $document->document_date->toIso8601String() : now()->toIso8601String()));

        $isValid = hash_equals($document->hash ?? '', $expectedHash);

        return response()->json([
            'is_valid' => $isValid,
            'data' => [
                'document_number' => $document->document_number,
                'reference' => $document->reference,
                'subject' => $document->subject,
                'document_type' => $document->document_type,
                'status' => $document->status,
                'confidentiality' => $document->confidentiality,
                'document_date' => optional($document->document_date)->toDateString(),
                'hash' => $document->hash,
                'qr_code_path' => $document->qr_code_path,
                'qr_anchor' => hash('sha256', 'AdminFlow|qr|'.(string) $document->hash),
                'is_archived' => (bool) $document->is_archived,
                'author' => $document->author ? [
                    'id' => $document->author->id,
                    'name' => $document->author->name,
                ] : null,
                'department' => $document->department ? [
                    'id' => $document->department->id,
                    'name' => $document->department->name,
                ] : null,
                'signatures' => $document->signatures->map(fn ($ds) => [
                    'signature_id' => $ds->signature_id,
                    'type' => $ds->type,
                    'hash_signature' => $ds->hash_signature,
                    'signed_at' => optional($ds->signed_at)->toIso8601String(),
                    'signed_by' => $ds->signer ? [
                        'id' => $ds->signer->id,
                        'name' => $ds->signer->name,
                    ] : null,
                ])->values(),
            ],
        ]);
    }
}
