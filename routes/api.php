<?php

use App\Http\Controllers\Api\ArchiveBoxController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DirectorInboxController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\MailMergeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SignatureController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkflowController;
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
        // Endpoints non authentifiés : limités pour prévenir le bruteforce.
        Route::middleware('throttle:auth')->group(function () {
            Route::post('login', [AuthController::class, 'login']);
            Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('reset-password', [AuthController::class, 'resetPassword']);
        });

        // Nombre de documents en attente (public, agrégé, pour la page de connexion)
        Route::get('director-pending-count', [DirectorInboxController::class, 'pendingCount']);

        Route::middleware('auth:sanctum')->group(function () {
            // La création de comptes est un acte d'administration : elle ne peut pas
            // être publique, sinon n'importe qui s'attribue le rôle de son choix.
            Route::post('register', [AuthController::class, 'register'])->middleware('role:admin');
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // ==================== ROUTES PROTÉGÉES ====================
    Route::middleware('auth:sanctum')->group(function () {

        // ---- UTILISATEURS ----
        Route::get('users/by-role/{role}', [UserController::class, 'byRole']);
        Route::apiResource('users', UserController::class)->only(['index', 'show']);
        Route::apiResource('users', UserController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware('role:admin');
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
        // ---- WORKFLOW « ENVOI À LA SIGNATURE » (Directeur de Cabinet) ----
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
        // Le paramètre 'archive_box' est important pour le route model binding dans UpdateArchiveBoxRequest
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
        Route::get('roles', [RoleController::class, 'roles']);
        Route::get('permissions', [RoleController::class, 'permissions']);

        // ---- TABLEAU DE BORD ----
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    });
});
