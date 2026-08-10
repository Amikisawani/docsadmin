<?php

namespace App\Http\Controllers\Api;

use App\Application\Signatures\SignDocumentUseCase;
use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\Signature;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    public function __construct(
        private readonly SignDocumentUseCase $signDocumentUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {

        $signatures = Signature::with('user')
            ->where('user_id', $request->user()->id)
            ->when($request->type, fn ($q, $type) => $q->byType($type))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json(['data' => $signatures]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:graphical,digital,certificate'],
            'label' => ['nullable', 'string', 'max:255'],
            'image' => ['required_if:type,graphical', 'image', 'mimes:png', 'max:1024'],
            'certificate_data' => ['required_if:type,certificate', 'nullable', 'string'],
            'certificate_serial' => ['required_if:type,certificate', 'nullable', 'string'],
            'certificate_expires_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'json'],
        ]);

        $data = [
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'label' => $validated['label'] ?? null,
            'certificate_data' => $validated['certificate_data'] ?? null,
            'certificate_serial' => $validated['certificate_serial'] ?? null,
            'certificate_expires_at' => $validated['certificate_expires_at'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('signatures', 'public');
        }

        // If first signature, set as default
        if (Signature::where('user_id', $request->user()->id)->count() === 0) {
            $data['is_default'] = true;
        }

        $signature = Signature::create($data);

        return response()->json([
            'message' => 'Signature créée avec succès.',
            'data' => $signature->load('user'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $signature = Signature::with('user', 'documentSignatures')->findOrFail($id);

        return response()->json(['data' => $signature]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $signature = Signature::findOrFail($id);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'json'],
        ]);

        if (isset($validated['is_default']) && $validated['is_default']) {
            Signature::where('user_id', $signature->user_id)->update(['is_default' => false]);
        }

        $signature->update($validated);

        return response()->json([
            'message' => 'Signature mise à jour.',
            'data' => $signature->fresh()->load('user'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $signature = Signature::findOrFail($id);

        if ($signature->image_path) {
            Storage::disk('public')->delete($signature->image_path);
        }

        $signature->delete();

        return response()->json([
            'message' => 'Signature supprimée.',
        ]);
    }

    // ==================== SIGNER UN DOCUMENT ====================

    public function canSign(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'can_sign' => $user->canSignDocuments(),
                'current_roles' => $user->getRoleNames()->values(),
                'required_roles' => User::signingRoles(),
            ],
        ]);
    }

    public function signDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => ['required', 'string', 'exists:documents,id'],
            'signature_id' => ['required', 'string', 'exists:signatures,id'],
            'position' => ['nullable', 'array'],
            'is_mail_merge' => ['nullable', 'boolean'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $docSignature = $this->signDocumentUseCase->execute($validated, $request->user());

        return response()->json([
            'message' => 'Document signé avec succès.',
            'data' => $docSignature,
        ], 201);
    }

    public function verifyDocument(string $documentId): JsonResponse
    {
        $document = Document::with(['signatures.signer', 'signatures.signature'])->findOrFail($documentId);

        $verification = [
            'document' => [
                'number' => $document->document_number,
                'subject' => $document->subject,
                'hash' => $document->hash,
            ],
            'signatures' => $document->signatures->map(fn ($sig) => [
                'signed_by' => $sig->signer?->name,
                'type' => $sig->type,
                'hash_signature' => $sig->hash_signature,
                'signed_at' => $sig->signed_at,
            ]),
            'is_valid' => $document->hash !== null,
        ];

        return response()->json(['data' => $verification]);
    }
}
