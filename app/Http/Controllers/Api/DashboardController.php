<?php

namespace App\Http\Controllers\Api;

use App\Domains\Departments\Models\Department;
use App\Domains\Documents\Models\Document;
use App\Domains\Users\Models\User;
use App\Domains\Workflows\Models\WorkflowApproval;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $countsByStatus = Document::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'total_documents' => (int) $countsByStatus->sum(),
                'draft_documents' => (int) $countsByStatus->get('draft', 0),
                'pending_documents' => (int) $countsByStatus->get('pending', 0),
                'approved_documents' => (int) $countsByStatus->get('approved', 0),
                'signed_documents' => (int) $countsByStatus->get('signed', 0),
                'rejected_documents' => (int) $countsByStatus->get('rejected', 0),
                'archived_documents' => Document::where('is_archived', true)->count(),
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'total_departments' => Department::count(),
                'pending_approvals' => WorkflowApproval::where('status', 'pending')->count(),
                'active_workflows' => WorkflowInstance::where('status', 'in_progress')->count(),
                'documents_by_type' => Document::query()
                    ->selectRaw('document_type, count(*) as total')
                    ->groupBy('document_type')
                    ->pluck('total', 'document_type'),
                'documents_by_month' => $this->documentsByMonth(),
                'documents_by_confidentiality' => Document::query()
                    ->selectRaw('confidentiality, count(*) as total')
                    ->groupBy('confidentiality')
                    ->pluck('total', 'confidentiality'),
                'recent_activities' => Activity::with('causer')->latest()->limit(10)->get(),
            ],
        ]);
    }

    /**
     * Nombre de documents par mois (`YYYY-MM`), indépendant du SGBD :
     * PostgreSQL et SQLite ne partagent aucune fonction de formatage de date.
     */
    private function documentsByMonth(): array
    {
        $driver = DB::connection()->getDriverName();

        $month = match ($driver) {
            'pgsql' => "to_char(document_date, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', document_date)",
            'mysql', 'mariadb' => "date_format(document_date, '%Y-%m')",
            'sqlsrv' => "format(document_date, 'yyyy-MM')",
            default => 'document_date',
        };

        return Document::query()
            ->whereNotNull('document_date')
            ->selectRaw("{$month} as month, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->all();
    }
}
