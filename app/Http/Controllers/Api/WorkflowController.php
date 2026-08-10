<?php

namespace App\Http\Controllers\Api;

use App\Application\Workflows\ApproveWorkflowUseCase;
use App\Application\Workflows\RejectWorkflowUseCase;
use App\Application\Workflows\StartWorkflowUseCase;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowApproval;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly StartWorkflowUseCase $startWorkflowUseCase,
        private readonly ApproveWorkflowUseCase $approveWorkflowUseCase,
        private readonly RejectWorkflowUseCase $rejectWorkflowUseCase,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $workflows = Workflow::with('creator')
            ->when($request->document_type, fn ($q, $type) => $q->byDocumentType($type))
            ->when($request->search, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json(['data' => $workflows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'document_type' => ['required', 'string'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string'],
            'steps.*.role' => ['required', 'string'],
            'steps.*.order' => ['required', 'integer', 'min:1'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $workflow = Workflow::create($validated);

        return response()->json([
            'message' => 'Workflow créé avec succès.',
            'data' => $workflow->load('creator'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $workflow = Workflow::with('creator', 'instances')->findOrFail($id);

        return response()->json(['data' => $workflow]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $workflow = Workflow::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'document_type' => ['sometimes', 'string'],
            'steps' => ['sometimes', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string'],
            'steps.*.role' => ['required', 'string'],
            'steps.*.order' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $workflow->update($validated);

        return response()->json([
            'message' => 'Workflow mis à jour avec succès.',
            'data' => $workflow->fresh()->load('creator'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $workflow = Workflow::findOrFail($id);

        if ($workflow->instances()->whereIn('status', ['in_progress', 'pending'])->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer ce workflow car des instances sont en cours.',
            ], 409);
        }

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow supprimé avec succès.',
        ]);
    }

    // ==================== INSTANCES ====================

    public function instances(Request $request): JsonResponse
    {
        $instances = WorkflowInstance::with(['workflow', 'document', 'initiator', 'approvals.approver'])
            ->when($request->status, fn ($q, $status) => $q->byStatus($status))
            ->when($request->workflow_id, fn ($q, $id) => $q->where('workflow_id', $id))
            ->when($request->user_id, fn ($q, $id) => $q->where('initiated_by', $id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json(['data' => $instances]);
    }

    public function startWorkflow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_id' => ['required', 'string', 'exists:workflows,id'],
            'document_id' => ['required', 'string', 'exists:documents,id'],
        ]);

        $instance = $this->startWorkflowUseCase->execute($validated, $request->user());

        return response()->json([
            'message' => 'Workflow démarré avec succès.',
            'data' => $instance,
        ], 201);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
            'signature_path' => ['nullable', 'string'],
        ]);

        $instance = $this->approveWorkflowUseCase->execute($validated, $request->user(), $id);

        return response()->json([
            'message' => 'Approbation enregistrée.',
            'data' => $instance,
        ]);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $instance = $this->rejectWorkflowUseCase->execute($validated, $request->user(), $id);

        return response()->json([
            'message' => 'Document rejeté.',
            'data' => $instance,
        ]);
    }

    public function pendingApprovals(Request $request): JsonResponse
    {
        $approvals = WorkflowApproval::with(['workflowInstance.workflow', 'workflowInstance.document'])
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json(['data' => $approvals]);
    }

    /**
     * Envoie un rappel à l'approbateur d'une approbation en attente.
     * Autorisé : l'auteur du document, ou un admin.
     */
    public function remind(Request $request, string $id): JsonResponse
    {
        $approval = WorkflowApproval::with(['workflowInstance.document', 'workflowInstance.workflow', 'approver'])
            ->findOrFail($id);

        $actor = $request->user();

        // Autorisation : admin ou auteur du document
        $document = $approval->workflowInstance->document;
        $isAdmin = $actor->hasRole('admin');
        $isAuthor = $document->author_id === $actor->id;

        abort_unless($isAdmin || $isAuthor, 403, 'Vous n\'êtes pas autorisé à envoyer un rappel.');

        // Seules les approbations en attente peuvent être rappelées
        abort_unless($approval->status === 'pending', 409, 'Cette approbation a déjà été traitée.');

        $approver = $approval->approver;

        $this->notificationService->notify(
            $approver,
            'Rappel : document à valider',
            'Le document « '.$document->subject.' » vous attend à l\'étape « '.$approval->step_name.' ». Merci de traiter cette validation.',
            'warning',
            [
                'document_id' => $document->id,
                'document_number' => $document->document_number,
                'workflow_instance_id' => $approval->workflow_instance_id,
                'approval_id' => $approval->id,
                'step' => $approval->step_name,
                'action_url' => '/documents/'.$document->id,
                'reminder' => true,
            ]
        );

        return response()->json([
            'message' => 'Rappel envoyé à '.$approver->name.'.',
        ]);
    }
}
