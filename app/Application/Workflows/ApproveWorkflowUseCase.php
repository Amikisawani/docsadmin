<?php

namespace App\Application\Workflows;

use App\Application\MailMerge\RunMailMergeUseCase;
use App\Domains\Documents\Models\DocumentHistory;
use App\Domains\MailMerge\Models\MailMergeBatch;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\WorkflowApproval;
use App\Domains\Workflows\Models\WorkflowInstance;
use Illuminate\Support\Arr;

final class ApproveWorkflowUseCase
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly RunMailMergeUseCase $runMailMergeUseCase,
    ) {}

    public function execute(array $input, User $actor, string $approvalId): WorkflowInstance
    {
        $comment = $input['comment'] ?? null;
        $signaturePath = $input['signature_path'] ?? null;

        $approval = WorkflowApproval::query()
            ->with(['workflowInstance.document', 'workflowInstance.workflow'])
            ->findOrFail($approvalId);

        $instance = $approval->workflowInstance;
        $document = $instance->document;
        $workflow = $instance->workflow;

        // Authorization: only the pending approver can approve.
        abort_unless($approval->approver_id === $actor->id, 403, 'Action non autorisée.');
        abort_unless($approval->status === 'pending', 409, 'Approbation déjà traitée.');

        // Idempotence: if already approved, return current state
        if ($approval->status === 'approved') {
            return $instance->load(['workflow', 'document', 'approvals.approver']);
        }

        $approval->update([
            'status' => 'approved',
            'comment' => $comment,
            'signature_path' => $signaturePath,
            'action_at' => now(),
        ]);

        $steps = collect($workflow->steps)->sortBy('order')->values();
        $currentStepIndex = $steps->search(fn ($step) => ($step['name'] ?? null) === $approval->step_name);

        $nextStep = $steps->get($currentStepIndex + 1);

        if ($nextStep) {
            $instance->update([
                'current_step' => $nextStep['name'],
                'history' => array_merge($instance->history ?? [], [[
                    'step' => $nextStep['name'],
                    'status' => 'pending',
                    'started_at' => now()->toIso8601String(),
                ]]),
            ]);

            $nextRole = (string)($nextStep['role'] ?? '');
            $nextApprover = $nextRole !== '' ? User::role($nextRole)->first() : null;

            if ($nextApprover) {
                \App\Domains\Workflows\Models\WorkflowApproval::create([
                    'workflow_instance_id' => $instance->id,
                    'step_name' => $nextStep['name'],
                    'approver_id' => $nextApprover->id,
                    'status' => 'pending',
                ]);

                // Notification à l'approbateur suivant
                $this->notificationService->notify(
                    $nextApprover,
                    'Nouvelle approbation requise',
                    'Le document « ' . $document->subject . ' » vous attend à l\'étape « ' . $nextStep['name'] . ' ».',
                    'info',
                    [
                        'document_id' => $document->id,
                        'document_number' => $document->document_number,
                        'workflow_instance_id' => $instance->id,
                        'step' => $nextStep['name'],
                        'action_url' => '/documents/' . $document->id,
                    ]
                );
            }

            $document->update(['status' => 'pending']);
        } else {
            $instance->update([
                'current_step' => 'completed',
                'status' => 'completed',
                'completed_at' => now(),
                'history' => array_merge($instance->history ?? [], [[
                    'step' => 'completed',
                    'status' => 'approved',
                    'completed_at' => now()->toIso8601String(),
                ]]),
            ]);

            $document->update([
                'status' => 'approved',
                'current_workflow_instance_id' => $instance->id,
            ]);

            $this->triggerMailMergeGeneration($document);

            // Workflow terminé : notifier les signataires autorisés que le document est prêt.
            $this->notificationService->notifyRole(
                'directeur',
                'Document prêt à signer',
                'Le document « ' . $document->subject . ' » a terminé toutes les validations. Il est prêt pour la signature.',
                'success',
                [
                    'document_id' => $document->id,
                    'document_number' => $document->document_number,
                    'workflow_instance_id' => $instance->id,
                    'action_url' => '/documents/' . $document->id,
                ]
            );
        }

        // Audit journal (DocumentHistory)
        $document->histories()->create([
            'user_id' => $actor->id,
            'action' => 'workflow_approved',
            'description' => 'Approbation workflow - ' . $approval->step_name,
            'metadata' => [
                'approval_id' => $approval->id,
                'step' => $approval->step_name,
            ],
        ]);

        return $instance->fresh()->load(['workflow', 'document', 'approvals.approver']);
    }

    private function triggerMailMergeGeneration(
        \App\Domains\Documents\Models\Document $document,
    ): void {
        $batches = MailMergeBatch::query()
            ->where('document_id', $document->id)
            ->where('status', 'awaiting_workflow')
            ->get();

        foreach ($batches as $batch) {
            $this->runMailMergeUseCase->generate($batch);
        }
    }
}
