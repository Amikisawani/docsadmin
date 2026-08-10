<?php

namespace App\Domains\Documents\Actions;

use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentHistory;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDocumentAction
{
    public function execute(array $data, string $userId): Document
    {
        // flow_type : 'unique' | 'mail_merge'
        // Version Présidence : le workflow n'est plus obligatoire à la création.
        // Le document est créé en « brouillon » ; l'auteur l'envoie ensuite
        // explicitement à la signature du Directeur de Cabinet.
        $flowType = $data['flow_type'] ?? 'unique';

        return DB::transaction(function () use ($data, $userId, $flowType) {
            $document = Document::create([
                'document_number' => $this->generateDocumentNumber($data['document_type']),
                'reference' => $data['reference'] ?? null,
                'subject' => $data['subject'],
                'document_type' => $data['document_type'],
                'author_id' => $userId,
                'department_id' => $data['department_id'] ?? null,
                'workflow_id' => $data['workflow_id'] ?? null,
                'source_file_path' => $data['source_file_path'] ?? null,
                'version' => '1.0',
                'status' => 'draft',
                'flow_type' => $flowType,
                'is_mail_merge' => $flowType === 'mail_merge',
                'confidentiality' => $data['confidentiality'] ?? 'interne',
                'document_date' => $data['document_date'] ?? now(),
                'content' => $data['content'] ?? null,
                'hash' => hash('sha256', ($data['content'] ?? '').now()->toIso8601String()),
            ]);

            // Log creation
            DocumentHistory::create([
                'document_id' => $document->id,
                'user_id' => $userId,
                'action' => 'created',
                'description' => 'Document créé',
                'metadata' => [
                    'document_type' => $data['document_type'],
                    'subject' => $data['subject'],
                ],
            ]);

            // causedBy() must receive a Model instance (not an ID string),
            // otherwise spatie/laravel-activitylog's CauserResolver tries to
            // resolve the provider and hits method_exists(null, ...).
            if ($user = User::find($userId)) {
                activity()
                    ->performedOn($document)
                    ->causedBy($user)
                    ->withProperties(['document_number' => $document->document_number])
                    ->log('document_created');
            }

            // Version Présidence : plus de démarrage automatique de workflow.
            // Le document reste en « brouillon » ; l'auteur l'envoie ensuite
            // explicitement à la signature du Directeur de Cabinet.

            return $document->fresh()->load('author', 'department', 'workflow', 'currentWorkflowInstance');
        });
    }

    private function generateDocumentNumber(string $type): string
    {
        $prefix = match ($type) {
            'courrier_entrant' => 'CE',
            'courrier_sortant' => 'CS',
            'note' => 'NT',
            'decision' => 'DC',
            'arrete' => 'AR',
            'decret' => 'DR',
            'circulaire' => 'CR',
            'rapport' => 'RP',
            'contrat' => 'CT',
            'convention' => 'CV',
            'demande' => 'DM',
            'mission' => 'MS',
            'facture' => 'FC',
            default => 'DOC',
        };

        $year = now()->format('Y');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$year}-{$random}";
    }
}
