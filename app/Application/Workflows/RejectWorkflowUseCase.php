<?php

namespace App\Application\Workflows;

use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\WorkflowApproval;
use App\Domains\Workflows\Models\WorkflowInstance;

final class RejectWorkflowUseCase
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(array $input, User $actor, string $approvalId): WorkflowInstance
    {
        $comment = $input['comment'] ?? null;

        $approval = WorkflowApproval::query()
            ->with(['workflowInstance.document', 'workflowInstance.workflow'])
            ->findOrFail($approvalId);

        $instance = $approval->workflowInstance;
        $document = $instance->document;

        abort_unless($approval->approver_id === $actor->id, 403, 'Action non autorisée.');
        abort_unless($approval->status === 'pending', 409, 'Approbation déjà traitée.');

        $approval->update([
            'status' => 'rejected',
            'comment' => $comment,
            'action_at' => now(),
        ]);

        $instance->update([
            'status' => 'rejected',
            'current_step' => 'rejected',
            'completed_at' => now(),
            'history' => array_merge($instance->history ?? [], [[
                'step' => $approval->step_name,
                'status' => 'rejected',
                'comment' => $comment,
                'rejected_at' => now()->toIso8601String(),
            ]]),
        ]);

        $document->update(['status' => 'rejected']);

        // Notification à l'initiateur du document
        $initiator = $instance->initiator ?? $document->author;
        if ($initiator) {
            $this->notificationService->notify(
                $initiator,
                'Document rejeté',
                'Le document « '.$document->subject.' » a été rejeté à l\'étape « '.$approval->step_name.' ». Motif : '.($comment ?: 'non précisé'),
                'error',
                [
                    'document_id' => $document->id,
                    'document_number' => $document->document_number,
                    'workflow_instance_id' => $instance->id,
                    'step' => $approval->step_name,
                    'action_url' => '/documents/'.$document->id,
                ]
            );
        }

        $document->histories()->create([
            'user_id' => $actor->id,
            'action' => 'workflow_rejected',
            'description' => 'Rejet workflow - '.$approval->step_name,
            'metadata' => [
                'approval_id' => $approval->id,
                'step' => $approval->step_name,
            ],
        ]);

        return $instance->fresh()->load(['workflow', 'document', 'approvals.approver']);
    }
}
