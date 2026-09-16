<?php

namespace App\Http\Controllers\Api;

use App\Application\Signatures\RecallSignatureUseCase;
use App\Application\Signatures\RejectForSignatureUseCase;
use App\Application\Signatures\SubmitForSignatureUseCase;
use App\Domains\Documents\Actions\CreateDocumentAction;
use App\Domains\Documents\Models\Document;
use App\Domains\Signatures\Models\Signature;
use App\Application\Signatures\SignDocumentUseCase;
use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly CreateDocumentAction $createDocumentAction,
        private readonly \App\Application\Archives\ArchiveDocumentUseCase $archiveDocumentUseCase,
        private readonly SubmitForSignatureUseCase $submitForSignatureUseCase,
        private readonly RecallSignatureUseCase $recallSignatureUseCase,
        private readonly RejectForSignatureUseCase $rejectForSignatureUseCase,
        private readonly SignDocumentUseCase $signDocumentUseCase,
    ) {}


public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Guard : le Directeur de Cabinet n'utilise pas la liste générique des documents.
        // Il passe par sa boîte de réception de validation (/director/inbox).
        abort_if($user->hasRole('directeur_cabinet'), 403, 'Le Directeur de Cabinet utilise sa boîte de validation.');

        $query = Document::with(['author', 'department', 'attachments', 'workflow', 'currentWorkflowInstance.approvals.approver'])
            ->notDeleted();

        // Visibilité par rôle : seuls l'admin et le directeur (hors cabinet) voient tous les documents.
        // Tout autre utilisateur ne voit que les documents qu'il a créés.
        if (!$user->hasRole('admin') && !$user->hasRole('directeur')) {
            $query->where('author_id', $user->id);
        }

        $documents = $query
            ->when($request->type, fn($q, $type) => $q->byType($type))
            ->when($request->status, fn($q, $status) => $q->byStatus($status))
            ->when($request->confidentiality, fn($q, $level) => $q->byConfidentiality($level))
            ->when($request->search, fn($q, $term) => $q->search($term))
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->author_id, fn($q, $id) => $q->where('author_id', $id))
            ->when($request->date_from, fn($q, $date) => $q->whereDate('document_date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->whereDate('document_date', '<=', $date))
            ->orderBy(
                Access::sanitizeSort($request->sort, ['created_at', 'updated_at', 'document_date', 'subject', 'status', 'document_number'], 'created_at'),
                Access::sanitizeOrder($request->order)
            )
            ->paginate(Access::perPage($request->per_page));

        return response()->json([
            'data' => $documents,
        ]);
    }

    /**
     * Retourne le détail du workflow d'un document :
     * étapes, approbations (qui a approuvé, qui doit approuver),
     * étape courante, statut de l'instance.
     */
    public function workflowProgress(Request $request, string $id): JsonResponse
    {
        $document = Document::with([
            'workflow',
            'currentWorkflowInstance.approvals.approver',
            'currentWorkflowInstance.workflow',
        ])->findOrFail($id);

        Access::ensureCanViewDocument($request->user(), $document);

        if (!$document->current_workflow_instance_id) {
            return response()->json([
                'data' => null,
                'message' => 'Aucun workflow en cours pour ce document.',
            ]);
        }

        $instance = $document->currentWorkflowInstance;

        // Calcul du progrès : combien d'étapes sont validées
        $totalSteps = $instance->workflow ? count($instance->workflow->steps ?? []) : 0;
        $approvedSteps = $instance->approvals->where('status', 'approved')->count();
        $pendingApprovals = $instance->approvals->where('status', 'pending');

        $progress = [
            'instance' => $instance,
            'workflow' => $instance->workflow,
            'total_steps' => $totalSteps,
            'completed_steps' => $approvedSteps,
            'current_step' => $instance->current_step,
            'status' => $instance->status,
            'pending_approvals' => $pendingApprovals->map(fn ($a) => [
                'approval_id' => $a->id,
                'step_name' => $a->step_name,
                'approver' => $a->approver ? [
                    'id' => $a->approver->id,
                    'name' => $a->approver->name,
                    'email' => $a->approver->email,
                ] : null,
                'created_at' => $a->created_at,
            ]),
            'all_approvals' => $instance->approvals->map(fn ($a) => [
                'approval_id' => $a->id,
                'step_name' => $a->step_name,
                'approver' => $a->approver ? [
                    'id' => $a->approver->id,
                    'name' => $a->approver->name,
                    'email' => $a->approver->email,
                ] : null,
                'status' => $a->status,
                'action_at' => $a->action_at,
                'comment' => $a->comment,
            ]),
        ];

        return response()->json(['data' => $progress]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:500'],
            'document_type' => ['required', 'string', 'in:courrier_entrant,courrier_sortant,note,notification,decision,arrete,decret,circulaire,proces_verbal,rapport,contrat,convention,demande,conge,mission,facture,autre'],
'flow_type' => ['sometimes', 'string', 'in:unique,mail_merge'],
            // Version Présidence : le workflow n'est plus obligatoire à la création.
            // Le document est créé en brouillon, puis envoyé à la signature du Directeur de Cabinet.
            'workflow_id' => ['nullable', 'string', 'exists:workflows,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'string', 'exists:departments,id'],
            'confidentiality' => ['nullable', 'string', 'in:public,interne,confidentiel,secret'],
            'document_date' => ['nullable', 'date'],
            'content' => ['nullable', 'string'],
            'source_file' => ['nullable', 'file', 'mimes:doc,docx,odt,pdf,txt', 'max:20480'],
        ]);

        // Valeur par défaut 'unique' si flow_type absent (rétro-compatibilité)
        $validated['flow_type'] = $validated['flow_type'] ?? 'unique';

        // Stocker le fichier Word source s'il est fourni
        if ($request->hasFile('source_file')) {
            $validated['source_file_path'] = $request->file('source_file')->store('documents/sources', 'public');
        }

        $document = $this->createDocumentAction->execute($validated, $request->user()->id);

        return response()->json([
            'message' => 'Document créé avec succès.',
            'data' => $document,
        ], 201);
    }

public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $document = Document::with(['author', 'department', 'attachments', 'signatures.signer', 'histories.user', 'workflow', 'currentWorkflowInstance.approvals.approver', 'currentWorkflowInstance.workflow'])
            ->findOrFail($id);

        Access::ensureCanViewDocument($user, $document);

        return response()->json([
            'data' => $document,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        Access::ensureCanModifyDocument($request->user(), $document);

        $validated = $request->validate([
            'subject' => ['sometimes', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:255'],
            'confidentiality' => ['nullable', 'string', 'in:public,interne,confidentiel,secret'],
            'content' => ['nullable', 'string'],
        ]);

        $document->update($validated);

        // Log update
        $document->histories()->create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'description' => 'Document mis à jour',
            'metadata' => $validated,
        ]);

        return response()->json([
            'message' => 'Document mis à jour avec succès.',
            'data' => $document->fresh()->load('author', 'department'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        Access::ensureCanModifyDocument($request->user(), $document);
        $document->update(['is_deleted' => true]);

        return response()->json([
            'message' => 'Document supprimé avec succès.',
        ]);
    }

    public function history(Request $request, string $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        Access::ensureCanViewDocument($request->user(), $document);
        $histories = $document->histories()->with('user')->orderBy('created_at', 'desc')->paginate(50);

        return response()->json(['data' => $histories]);
    }

    public function attachments(Request $request, string $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        Access::ensureCanViewDocument($request->user(), $document);
        return response()->json(['data' => $document->attachments]);
    }

    public function uploadAttachment(Request $request, string $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        Access::ensureCanModifyDocument($request->user(), $document);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,odt,jpg,jpeg,png,txt,xls,xlsx', 'max:20480'],
            'type' => ['nullable', 'string', 'in:attachment,annex,appendice'],
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments/' . $document->id, 'public');

        $attachment = $document->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'hash' => hash_file('sha256', $file->getRealPath()),
            'type' => $validated['type'] ?? 'attachment',
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Fichier joint ajouté.',
            'data' => $attachment,
        ], 201);
    }

    public function deleteAttachment(Request $request, string $id, string $attachmentId): JsonResponse
    {
        $document = Document::findOrFail($id);
        Access::ensureCanModifyDocument($request->user(), $document);
        $attachment = $document->attachments()->findOrFail($attachmentId);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->stored_path);
        $attachment->delete();

        return response()->json(['message' => 'Fichier supprimé.']);
    }

    public function archive(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => ['sometimes', 'string', 'exists:documents,id'],
            'archive_box_id' => ['nullable', 'string', 'exists:archive_boxes,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'conservation_duration' => ['nullable', 'string', 'max:255'],
            'conservation_until_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $document = Document::findOrFail($id);
        Access::ensureCanArchive($request->user(), $document);

        $validated['document_id'] = (string) $id;

        $archive = $this->archiveDocumentUseCase->execute($validated, $request->user());


        return response()->json([
            'message' => 'Document archivé avec succès.',
            'data' => $archive,
        ], 201);
    }

    public function types(): JsonResponse
    {
        return response()->json([
            'data' => [

                'courrier_entrant' => 'Courrier entrant',
                'courrier_sortant' => 'Courrier sortant',
                'note' => 'Note',
                'notification' => 'Notification',
                'decision' => 'Décision',
                'arrete' => 'Arrêté',
                'decret' => 'Décret',
                'circulaire' => 'Circulaire',
                'proces_verbal' => 'Procès-verbal',
                'rapport' => 'Rapport',
                'contrat' => 'Contrat',
                'convention' => 'Convention',
                'demande' => 'Demande',
                'conge' => 'Congé',
                'mission' => 'Mission',
'facture' => 'Facture',
                'autre' => 'Autre',
            ],
        ]);
    }

    // ==================== WORKFLOW D'ENVOI À LA SIGNATURE ====================

    /**
     * Envoie un document à la signature du Directeur de Cabinet.
     */
    public function submitForSignature(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'priority' => ['nullable', 'string', 'in:normale,haute,urgente'],
            'deadline' => ['nullable', 'date'],
        ]);

        $validated['document_id'] = (string) $id;

        $document = $this->submitForSignatureUseCase->execute($validated, $request->user());

        return response()->json([
            'message' => 'Document envoyé à la signature du Directeur de Cabinet.',
            'data' => $document,
        ]);
    }

    /**
     * Rappelle une demande de signature (document toujours en attente).
     */
    public function recallSignature(Request $request, string $id): JsonResponse
    {
        $document = $this->recallSignatureUseCase->execute(['document_id' => (string) $id], $request->user());

        return response()->json([
            'message' => 'Rappel de signature envoyé au Directeur de Cabinet.',
            'data' => $document,
        ]);
    }

    /**
     * Rejet d'un document par le Directeur de Cabinet (motif obligatoire).
     */
    public function rejectForSignature(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $validated['document_id'] = (string) $id;

        $document = $this->rejectForSignatureUseCase->execute($validated, $request->user());

        return response()->json([
            'message' => 'Document rejeté.',
            'data' => $document,
        ]);
    }

    /**
     * Signature d'un document par le Directeur de Cabinet (lui seul peut signer).
     */
    public function signByDirector(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'signature_id' => ['required', 'string', 'exists:signatures,id'],
            'position' => ['nullable', 'array'],
            'is_mail_merge' => ['nullable', 'boolean'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $validated['document_id'] = (string) $id;

        $docSignature = $this->signDocumentUseCase->execute($validated, $request->user());

        return response()->json([
            'message' => 'Document signé avec succès.',
            'data' => $docSignature,
        ], 201);
    }
}
