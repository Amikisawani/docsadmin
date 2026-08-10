<?php

namespace App\Application\Workflows;

use App\Domains\Documents\Models\Document;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowApproval;
use App\Domains\Workflows\Models\WorkflowInstance;

final class StartWorkflowUseCase
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(array $input, User $actor): WorkflowInstance
    {
        // Guard 0: authenticated actor is required
        if (! $actor instanceof User) {
            abort(401, 'Authentification requise.');
        }

        $workflow = Workflow::findOrFail($input['workflow_id']);
        $document = Document::findOrFail($input['document_id']);

        // Guard 1: document type compatibility (best-effort; workflow holds document_type)
        // Pour le publipostage, le document source est un document métier de type `mail_merge`
        // et le workflow de campagne peut être paramétré indépendamment du type métier.
        if (
            ! empty($workflow->document_type)
            && $document->flow_type !== 'mail_merge'
            && $document->document_type !== $workflow->document_type
        ) {
            abort(422, 'Type de document incompatible avec ce workflow.');
        }

        // Guard 2: idempotence - avoid starting twice for same document & workflow while in progress
        $existing = WorkflowInstance::query()
            ->where('workflow_id', $workflow->id)
            ->where('document_id', $document->id)
            ->whereIn('status', ['in_progress', 'pending'])
            ->first();

        if ($existing) {
            return $existing->load(['workflow', 'document', 'approvals.approver']);
        }

        $steps = collect($workflow->steps)->sortBy('order')->values();
        $firstStep = $steps->first();
        abort_if(! $firstStep, 422, 'Workflow invalide : aucune étape.');

        $instance = WorkflowInstance::create([
            'workflow_id' => $workflow->id,
            'document_id' => $document->id,
            'current_step' => $firstStep['name'],
            'status' => 'in_progress',
            'history' => [[
                'step' => $firstStep['name'],
                'status' => 'pending',
                'started_at' => now()->toIso8601String(),
            ]],
            'initiated_by' => $actor->id,
        ]);

        // The initiator is allowed to approve only if they match the first role.
        $approver = $this->resolveFirstApprover($firstStep, $actor);

        WorkflowApproval::create([
            'workflow_instance_id' => $instance->id,
            'step_name' => $firstStep['name'],
            'approver_id' => $approver->id,
            'status' => 'pending',
        ]);

        // Attacher le workflow et l'instance au document
        $document->update([
            'status' => 'pending',
            'workflow_id' => $workflow->id,
            'current_workflow_instance_id' => $instance->id,
        ]);

        // Notification au premier approbateur
        $this->notificationService->notify(
            $approver,
            'Document à approuver',
            'Un document vous attend pour validation : '.$document->subject,
            'info',
            [
                'document_id' => $document->id,
                'document_number' => $document->document_number,
                'workflow_instance_id' => $instance->id,
                'step' => $firstStep['name'],
                'action_url' => '/documents/'.$document->id,
            ]
        );

        return $instance->load(['workflow', 'document', 'approvals.approver']);
    }

    private function resolveFirstApprover(array $firstStep, User $actor): User
    {
        // Null-safe guard: never call methods on a null actor.
        if (! $actor instanceof User) {
            abort(401, 'Authentification requise.');
        }

        // If actor already matches the role, use them.
        $role = (string) ($firstStep['role'] ?? '');
        if ($role !== '' && $actor->hasRole($role)) {
            return $actor;
        }

        // Otherwise pick the first user with the role.
        // If spatie roles are not seeded/configured in this environment, fallback to the actor.
        try {
            if ($role === '') {
                return $actor;
            }

            return User::query()
                ->role($role)
                ->firstOrFail();
        } catch (\Throwable) {
            return $actor;
        }
    }
}
