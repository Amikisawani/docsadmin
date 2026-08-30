<?php

namespace App\Http\Controllers\Api;

use App\Application\MailMerge\RecipientFileParser;
use App\Application\MailMerge\RunMailMergeUseCase;
use App\Application\Signatures\SendCampaignToSignatureUseCase;
use App\Application\Signatures\SignMailMergeCampaignUseCase;
use App\Domains\Documents\Models\Document;
use App\Domains\MailMerge\Models\MailMergeBatch;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MailMergeController extends Controller
{
    /** Variables disponibles dans les documents de publipostage. */
    private const AVAILABLE_VARIABLES = [
        'nom', 'postnom', 'prenom', 'nom_complet', 'civilite', 'destinataire',
        'matricule', 'grade', 'fonction', 'service',
        'direction', 'administration', 'ministere',
        'adresse_administration', 'ville', 'email', 'telephone',
        'annee', 'date_notification', 'date_arrete', 'numero_arrete',
        'reference', 'nom_signataire', 'fonction_signataire',
    ];

public function __construct(
        private readonly RunMailMergeUseCase $runMailMergeUseCase,
        private readonly RecipientFileParser $recipientFileParser,
        private readonly SendCampaignToSignatureUseCase $sendCampaignToSignatureUseCase,
        private readonly SignMailMergeCampaignUseCase $signMailMergeCampaignUseCase,
    ) {}

/** Liste les campagnes de publipostage de l'utilisateur. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = MailMergeBatch::with(['template', 'document', 'creator', 'workflow', 'currentWorkflowInstance.approvals.approver', 'recipients']);

        // Le Directeur de Cabinet voit les campagnes qui lui sont envoyées pour signature.
        if ($user->hasRole('directeur_cabinet')) {
            $query->whereIn('status', ['pending_signature', 'signed', 'rejected']);
        } else {
            $query->where('created_by', $user->id);
        }

        $batches = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json(['data' => $batches]);
    }

    /** Détail d'un batch avec ses destinataires. */
    public function show(string $id): JsonResponse
    {
        $batch = MailMergeBatch::with(['template', 'document', 'creator', 'workflow', 'currentWorkflowInstance.approvals.approver', 'recipients'])->findOrFail($id);
        $this->authorizeBatch($batch, request()->user());

        return response()->json(['data' => $batch]);
    }

    /**
     * Retourne le détail du workflow d'une campagne de publipostage :
     * étapes, approbations (qui a approuvé, qui doit approuver),
     * étape courante, statut de l'instance.
     */
    public function workflowProgress(string $id): JsonResponse
    {
        $batch = MailMergeBatch::with([
            'workflow',
            'currentWorkflowInstance.approvals.approver',
            'currentWorkflowInstance.workflow',
            'document',
        ])->findOrFail($id);

        $this->authorizeBatch($batch, request()->user());

        if (!$batch->current_workflow_instance_id) {
            return response()->json([
                'data' => null,
                'message' => 'Aucun workflow en cours pour cette campagne.',
            ]);
        }

        $instance = $batch->currentWorkflowInstance;

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
            'batch_status' => $batch->status,
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

    /** Lance une campagne de publipostage. */
    public function store(Request $request): JsonResponse
    {
        // Le frontend envoie default_variables sous forme de chaîne JSON (FormData)
        if ($request->has('default_variables') && is_string($request->input('default_variables'))) {
            $decoded = json_decode((string) $request->input('default_variables'), true);
            $request->merge(['default_variables' => is_array($decoded) ? $decoded : []]);
        }

$validated = $request->validate([
            // Source : uniquement un document de type « publipostage »
            // Version Présidence : le workflow est optionnel → génération immédiate si absent.
            'document_id' => ['required', 'string', 'exists:documents,id'],
            'workflow_id' => ['nullable', 'string', 'exists:workflows,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'format' => ['sometimes', 'string', 'in:pdf,docx,txt'],

            // Destinataires : liste directe OU fichier agents
            'recipients' => ['nullable', 'array', 'min:1', 'max:5000'],
            'recipients.*.name' => ['required_with:recipients', 'string', 'max:255'],
            'recipients.*.destinataire' => ['nullable', 'string', 'max:255'],
            'recipients.*.variables' => ['nullable', 'array'],
            'recipients_file' => ['nullable', 'file', 'mimes:xls,xlsx,txt,csv,tsv', 'max:10240'],

            // Variables globales par défaut (pré-remplies/modifiables)
            'default_variables' => ['nullable', 'array'],
        ]);

        // Le document source doit être de type publipostage
        $sourceDocument = Document::query()->findOrFail($validated['document_id']);
        abort_unless(
            (string) $sourceDocument->author_id === (string) $request->user()->id
                || $request->user()->hasRole('admin'),
            403,
            'Vous ne pouvez publiposter que vos propres documents.'
        );
        if ($sourceDocument->flow_type !== 'mail_merge') {
            return response()->json([
                'message' => 'Seuls les documents de type « publipostage » peuvent être utilisés pour un publipostage.',
            ], 422);
        }

        // Upload du fichier destinataires (si fourni)
        $recipientsFilePath = null;
        if ($request->hasFile('recipients_file')) {
            $recipientsFilePath = $request->file('recipients_file')->store('mailmerge/recipients', 'public');
        }

        $input = $validated;
        $input['source_file_path'] = null;
        $input['recipients_file_path'] = $recipientsFilePath;

        $batch = $this->runMailMergeUseCase->execute($input, $request->user());

        $status = match ($batch->status) {
            'completed', 'partial', 'awaiting_workflow' => 201,
            default => 422,
        };

        $errors = array_filter((array) ($batch->errors ?? []), fn ($error) => is_string($error) && trim($error) !== '');
        $firstError = $errors[0] ?? null;

        $message = match ($batch->status) {
            'completed' => 'Publipostage généré : ' . $batch->generated_count . ' document(s) sur ' . $batch->total_recipients . '.',
            'partial' => 'Publipostage partiellement généré : ' . $batch->generated_count . ' document(s) sur ' . $batch->total_recipients . '.',
            'awaiting_workflow' => 'Campagne de publipostage créée. Le workflow de validation du document source a été démarré.',
            default => $firstError ?: 'Échec du publipostage. Aucun document généré.',
        };

        return response()->json([
            'message' => $message,
            'errors' => $errors,
            'data' => $batch->load(['template', 'document', 'creator', 'recipients']),
        ], $status);
    }

    /** Aperçu d'un fichier destinataires uploadé (en-têtes + premières lignes). */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'recipients_file' => ['required', 'file', 'mimes:xls,xlsx,txt,csv,tsv', 'max:10240'],
        ]);

        $file = $request->file('recipients_file');
        $fullPath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();

        if ($fullPath === false) {
            return response()->json(['message' => 'Fichier illisible.'], 422);
        }

        try {
            $preview = $this->recipientFileParser->preview($fullPath, $originalName);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Fichier illisible : ' . $e->getMessage()], 422);
        }

        return response()->json(['data' => $preview]);
    }

    /** Liste les documents éligibles au publipostage (documents source de type publipostage, y compris en brouillon). */
    public function documents(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->with('author')
            ->where('flow_type', 'mail_merge')
            ->where('is_deleted', false)
            ->whereIn('status', ['draft', 'approved', 'signed'])
            ->when(
                ! $request->user()->hasRole('admin'),
                fn ($q) => $q->where('author_id', $request->user()->id)
            )
            ->when($request->search, fn ($q, $term) => $q->search($term))
            ->orderBy('updated_at', 'desc')
            ->limit(100)
            ->get(['id', 'subject', 'document_number', 'reference', 'status', 'flow_type', 'document_date']);

        return response()->json(['data' => $documents]);
    }

    /** Liste les variables de fusion disponibles. */
    public function variables(): JsonResponse
    {
        return response()->json(['data' => self::AVAILABLE_VARIABLES]);
    }

    /** Télécharge l'archive ZIP d'un batch. */
    public function download(string $id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $batch = MailMergeBatch::with(['template', 'document', 'creator'])->findOrFail($id);
        $this->authorizeBatch($batch, request()->user());

        if (!$batch->zip_path || !Storage::disk('public')->exists($batch->zip_path)) {
            abort(404, 'Archive ZIP introuvable.');
        }

        $filename = ($this->slugify($batch->title ?? 'publipostage') ?: 'publipostage') . '.zip';

        return Storage::disk('public')->download($batch->zip_path, $filename);
    }

/**
     * Envoie une campagne de publipostage terminée à la signature.
     *
     * Le créateur (ou un admin) déclenche l'envoi : le statut passe à
     * « pending_signature », le Directeur de Cabinet est notifié et un
     * événement temps réel est diffusé.
     */
    public function sign(string $id): JsonResponse
    {
        $batch = $this->sendCampaignToSignatureUseCase->execute(
            ['batch_id' => (string) $id],
            request()->user()
        );

        return response()->json([
            'message' => 'Campagne envoyée à la signature du Directeur de Cabinet.',
            'data' => $batch,
        ]);
    }

/**
     * Rappelle une campagne de publipostage envoyée à la signature.
     *
     * Le créateur (ou un admin) retire la campagne de la boîte du Directeur de
     * Cabinet : le statut revient à « completed » (générée) et elle redevient
     * modifiable / ré-envoyable.
     */
    public function recallSignature(string $id): JsonResponse
    {
        $batch = MailMergeBatch::query()->findOrFail($id);
        $user = request()->user();

        // Guard : créateur de la campagne ou admin
        abort_unless(
            $batch->created_by === $user->id || $user->hasRole('admin') || $user->hasRole('directeur_cabinet'),
            403,
            'Vous ne pouvez pas rappeler cette campagne.'
        );

        // Guard : la campagne doit être en attente de signature (ou rejetée)
        abort_unless(
            in_array($batch->status, ['pending_signature', 'rejected'], true),
            409,
            'Cette campagne ne peut pas être rappelée car elle n\'est pas en attente de signature.'
        );

        $batch->update([
            'status' => 'completed',
            'submitted_for_signature_at' => null,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Campagne retirée de la signature. Elle est de nouveau disponible.',
            'data' => $batch->load('document', 'creator', 'recipients', 'workflow'),
        ]);
    }

    /**
     * Signature d'une campagne par le Directeur de Cabinet.
     *
     * Seul le Directeur de Cabinet (détenteur du pouvoir de signature) peut
     * signer la campagne. Sa signature est appliquée sur chaque PDF et le
     * tout est regroupé dans un ZIP signé.
     */
    public function signCampaign(Request $request, string $id): JsonResponse
    {
        // Guard : réservé au Directeur de Cabinet
        abort_unless(
            $request->user()->hasRole('directeur_cabinet'),
            403,
            'Seul le Directeur de Cabinet peut signer une campagne.'
        );

        $validated = $request->validate([
            'signature_id' => ['required', 'string', 'exists:signatures,id'],
            'position' => ['nullable', 'array'],
        ]);

        $validated['batch_id'] = (string) $id;

        $batch = $this->signMailMergeCampaignUseCase->execute($validated, $request->user());

        return response()->json([
            'message' => 'Campagne signée avec succès.',
            'data' => $batch,
        ], 201);
    }

    /** Supprime un batch et ses fichiers générés. */
    public function destroy(string $id): JsonResponse
    {
        $batch = MailMergeBatch::with('recipients')->findOrFail($id);
        $this->authorizeBatch($batch, request()->user());

        // Nettoyage des fichiers générés
        $disk = Storage::disk('public');
        foreach ($batch->recipients as $recipient) {
            if ($recipient->output_path && $disk->exists($recipient->output_path)) {
                $disk->delete($recipient->output_path);
            }
        }
        if ($batch->zip_path && $disk->exists($batch->zip_path)) {
            $disk->delete($batch->zip_path);
        }

        // Suppression du fichier source et des destinataires uploadés
        foreach (['source_file_path', 'recipients_file_path'] as $field) {
            if ($batch->{$field} && $disk->exists($batch->{$field})) {
                $disk->delete($batch->{$field});
            }
        }

        $batch->recipients()->delete();
        $batch->delete();

        return response()->json(['message' => 'Campagne de publipostage supprimée.']);
    }

    private function authorizeBatch(MailMergeBatch $batch, ?User $user): void
    {
        abort_unless($user, 401, 'Authentification requise.');

        if ($user->hasRole('directeur_cabinet')) {
            abort_unless(
                in_array($batch->status, ['pending_signature', 'signed', 'rejected'], true),
                403,
                'Cette campagne n\'est pas destinée à la signature.'
            );

            return;
        }

        if ($batch->created_by !== $user->id && ! $user->hasRole('admin')) {
            abort(403, 'Cette campagne ne vous appartient pas.');
        }
    }

    private function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?? $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim((string) $value, '-');
    }
}


