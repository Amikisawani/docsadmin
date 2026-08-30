<?php

use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\ArchiveBoxController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DirectorInboxController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\MailMergeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SignatureController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkflowController;
use App\Support\Access;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AdminFlow API Routes v1
|--------------------------------------------------------------------------
|
| API REST versionnée pour la plateforme de Gestion Electronique
| des Documents Administratifs.
|
*/

Route::prefix('v1')->group(function () {

    // ==================== AUTHENTIFICATION ====================
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::get('director-pending-count', [DirectorInboxController::class, 'pendingCount']);
        });
    });

    // ==================== ROUTES PROTÉGÉES ====================
    Route::middleware('auth:sanctum')->group(function () {

        // ---- UTILISATEURS ----
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/avatar', [UserController::class, 'uploadAvatar']);
        Route::post('users/{user}/signature', [UserController::class, 'uploadSignature']);

        // ---- DÉPARTEMENTS ----
        Route::get('departments/tree', [DepartmentController::class, 'tree']);
        Route::apiResource('departments', DepartmentController::class);

        // ---- DOCUMENTS ----
        Route::get('documents/types', [DocumentController::class, 'types']);
        Route::apiResource('documents', DocumentController::class);
        Route::get('documents/{document}/history', [DocumentController::class, 'history']);
        Route::get('documents/{document}/attachments', [DocumentController::class, 'attachments']);
        Route::post('documents/{document}/attachments', [DocumentController::class, 'uploadAttachment']);
        Route::delete('documents/{document}/attachments/{attachment}', [DocumentController::class, 'deleteAttachment']);
        Route::post('documents/{document}/archive', [DocumentController::class, 'archive']);
        Route::get('documents/{document}/workflow-progress', [DocumentController::class, 'workflowProgress']);
        Route::post('documents/{document}/submit-for-signature', [DocumentController::class, 'submitForSignature']);
        Route::post('documents/{document}/recall-signature', [DocumentController::class, 'recallSignature']);
        Route::post('documents/{document}/reject', [DocumentController::class, 'rejectForSignature']);
        Route::post('documents/{document}/sign', [DocumentController::class, 'signByDirector']);

        // ---- TEMPLATES (MODÈLES) ----
        Route::get('templates/variables', [TemplateController::class, 'variables']);
        Route::apiResource('templates', TemplateController::class);
        Route::post('templates/{template}/generate', [TemplateController::class, 'generate']);

        // ---- WORKFLOWS ----
        Route::get('workflows/instances', [WorkflowController::class, 'instances']);
        Route::post('workflows/start', [WorkflowController::class, 'startWorkflow']);
        Route::post('workflows/approvals/{approval}/approve', [WorkflowController::class, 'approve']);
        Route::post('workflows/approvals/{approval}/reject', [WorkflowController::class, 'reject']);
        Route::get('workflows/pending-approvals', [WorkflowController::class, 'pendingApprovals']);
        Route::post('workflows/approvals/{approval}/remind', [WorkflowController::class, 'remind']);
        Route::apiResource('workflows', WorkflowController::class);

        // ---- SIGNATURES ----
        Route::get('signatures/can-sign', [SignatureController::class, 'canSign']);
        Route::post('signatures/sign-document', [SignatureController::class, 'signDocument']);
        Route::get('signatures/verify/{document}', [SignatureController::class, 'verifyDocument']);
        Route::apiResource('signatures', SignatureController::class);

        // ---- PUBLIPOSTAGE (MAIL MERGE) ----
        Route::post('mail-merge/preview', [MailMergeController::class, 'preview']);
        Route::get('mail-merge/documents', [MailMergeController::class, 'documents']);
        Route::get('mail-merge/variables', [MailMergeController::class, 'variables']);
        Route::get('mail-merge/{batch}/download', [MailMergeController::class, 'download']);
        Route::get('mail-merge/{batch}/workflow-progress', [MailMergeController::class, 'workflowProgress']);
        Route::post('mail-merge/{batch}/sign', [MailMergeController::class, 'sign']);
        Route::post('mail-merge/{batch}/recall-signature', [MailMergeController::class, 'recallSignature']);
        Route::post('mail-merge/{batch}/sign-campaign', [MailMergeController::class, 'signCampaign']);
        Route::apiResource('mail-merge', MailMergeController::class)->parameters(['mail-merge' => 'batch']);

        // ---- ARCHIVES ----
        Route::apiResource('archive-boxes', ArchiveBoxController::class)->parameters(['archive-boxes' => 'archive_box']);
        Route::get('archives/eligible-documents', [ArchiveController::class, 'eligibleDocuments']);
        Route::apiResource('archives', ArchiveController::class);

        // ---- NOTIFICATIONS ----
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::get('notifications/preferences', [NotificationController::class, 'preferences']);
        Route::put('notifications/preferences', [NotificationController::class, 'updatePreferences']);
        Route::get('notifications', [NotificationController::class, 'index']);

        // ---- BOÎTE DE RÉCEPTION DU DIRECTEUR DE CABINET ----
        Route::get('director/inbox', [DirectorInboxController::class, 'index']);
        Route::get('director/stats', [DirectorInboxController::class, 'stats']);
        Route::get('director/history', [DirectorInboxController::class, 'history']);
        Route::get('director/recent-rejected', [DirectorInboxController::class, 'recentRejected']);

        // ---- AUDIT / JOURNAL ----
        Route::get('audit/events', [AuditController::class, 'events']);
        Route::get('audit/stats', [AuditController::class, 'stats']);
        Route::get('audit/signatures-history', [AuditController::class, 'signatures']);
        Route::get('audit/rejections-history', [AuditController::class, 'rejections']);
        Route::get('audit/director-activity', [AuditController::class, 'directorActivity']);
        Route::get('audit/{log}', [AuditController::class, 'show']);
        Route::get('audit', [AuditController::class, 'index']);

        // ---- RÔLES & PERMISSIONS ----
        Route::get('roles', function () {
            Access::ensureAdmin(request()->user());

            return response()->json([
                'data' => \Spatie\Permission\Models\Role::with('permissions')->get(),
            ]);
        });
        Route::get('permissions', function () {
            Access::ensureAdmin(request()->user());

            return response()->json([
                'data' => \Spatie\Permission\Models\Permission::all(),
            ]);
        });

        // ---- TABLEAU DE BORD ----
        Route::get('dashboard/stats', function () {
            $user = request()->user();
            $global = Access::canViewGlobalStats($user);

            $documents = \App\Domains\Documents\Models\Document::query();
            if (! $global) {
                $documents->where('author_id', $user->id);
            }

            $stats = [
                'total_documents' => (clone $documents)->count(),
                'draft_documents' => (clone $documents)->where('status', 'draft')->count(),
                'pending_documents' => (clone $documents)->where('status', 'pending')->count(),
                'approved_documents' => (clone $documents)->where('status', 'approved')->count(),
                'signed_documents' => (clone $documents)->where('status', 'signed')->count(),
                'rejected_documents' => (clone $documents)->where('status', 'rejected')->count(),
                'archived_documents' => (clone $documents)->where('is_archived', true)->count(),
                'total_users' => $global ? \App\Domains\Users\Models\User::count() : 1,
                'active_users' => $global ? \App\Domains\Users\Models\User::active()->count() : 1,
                'total_departments' => $global ? \App\Domains\Departments\Models\Department::count() : 0,
                'pending_approvals' => \App\Domains\Workflows\Models\WorkflowApproval::query()
                    ->where('status', 'pending')
                    ->when(! $global, fn ($q) => $q->where('approver_id', $user->id))
                    ->count(),
                'active_workflows' => \App\Domains\Workflows\Models\WorkflowInstance::query()
                    ->where('status', 'in_progress')
                    ->when(! $global, fn ($q) => $q->where('initiated_by', $user->id))
                    ->count(),
                'documents_by_type' => (clone $documents)->selectRaw('document_type, count(*) as total')
                    ->groupBy('document_type')->pluck('total', 'document_type'),
                'documents_by_month' => (clone $documents)->selectRaw(
                    (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite'
                        ? "strftime('%Y-%m', document_date)"
                        : "to_char(document_date, 'YYYY-MM')") . ' as month, count(*) as total'
                )
                    ->groupBy('month')->orderBy('month')->pluck('total', 'month'),
                'documents_by_confidentiality' => (clone $documents)->selectRaw('confidentiality, count(*) as total')
                    ->groupBy('confidentiality')->pluck('total', 'confidentiality'),
                'recent_activities' => $global
                    ? \Spatie\Activitylog\Models\Activity::with('causer')->latest()->limit(10)->get()
                    : \Spatie\Activitylog\Models\Activity::with('causer')
                        ->where('causer_id', $user->id)
                        ->latest()->limit(10)->get(),
            ];

            return response()->json(['data' => $stats]);
        });
    });
});
