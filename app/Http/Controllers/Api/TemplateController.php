<?php

namespace App\Http\Controllers\Api;

use App\Domains\Templates\Models\Template;
use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = Template::with('creator')
            ->when($request->type, fn($q, $type) => $q->byType($type))
            ->when($request->category, fn($q, $cat) => $q->byCategory($cat))
            ->when($request->search, fn($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json(['data' => $templates]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:word,pdf,text'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Deux modes possibles : import fichier OU contenu saisi
            'file' => ['required_without:content', 'nullable', 'file', 'mimes:docx,pdf,txt,md', 'max:10240'],
            'content' => ['required_without:file', 'nullable', 'string', 'max:100000'],
            'variables' => ['nullable', 'array'],
        ]);

        // Mode 1 : import fichier
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->storeAs(
                'templates',
                Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)) . '.' . $file->getClientOriginalExtension(),
                'public'
            );
            $content = null;
        }
        // Mode 2 : contenu saisi (textarea)
        else {
            $slug = Str::slug($validated['name']) ?: 'template';
            $path = "templates/{$slug}-" . Str::lower(Str::random(6)) . '.txt';
            Storage::disk('public')->put($path, $validated['content']);
            $content = $validated['content'];
        }

        $template = Template::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'content' => $content,
            'variables' => isset($validated['variables']) && count($validated['variables']) ? $validated['variables'] : null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Modèle créé avec succès.',
            'data' => $template->load('creator'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = Template::with('creator')->findOrFail($id);
        return response()->json(['data' => $template]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $template = Template::findOrFail($id);
        abort_unless(
            Access::isAdmin($request->user()) || (string) $template->created_by === (string) $request->user()->id,
            403,
            'Vous n\'êtes pas autorisé à modifier ce modèle.'
        );

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:word,pdf,text'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string', 'max:100000'],
            'file' => ['sometimes', 'nullable', 'file', 'mimes:docx,pdf,txt,md', 'max:10240'],
            'variables' => ['nullable', 'json'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Remplacement de fichier : on supprime l'ancien et on en stocke un nouveau.
        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($template->file_path);
            $file = $request->file('file');
            $validated['file_path'] = $file->storeAs(
                'templates',
                Str::slug(($validated['name'] ?? $template->name) ?: 'template') . '-' . Str::lower(Str::random(6)) . '.' . $file->getClientOriginalExtension(),
                'public'
            );
            $validated['content'] = null;
        }
        // Mode contenu : on met à jour le contenu texte et on réécrit le fichier source.
        elseif ($request->filled('content')) {
            $path = $template->file_path;
            if ($template->content === null || $template->file_path && !str_ends_with($template->file_path, '.txt')) {
                $path = Str::slug(($validated['name'] ?? $template->name) ?: 'template') . '-' . Str::lower(Str::random(6)) . '.txt';
                Storage::disk('public')->put($path, $validated['content']);
                $validated['file_path'] = $path;
            } else {
                Storage::disk('public')->put($path, $validated['content']);
            }
            $validated['content'] = $validated['content'];
        }

        $template->update($validated);

        return response()->json([
            'message' => 'Modèle mis à jour.',
            'data' => $template->fresh()->load('creator'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $template = Template::findOrFail($id);
        abort_unless(
            Access::isAdmin($request->user()) || (string) $template->created_by === (string) $request->user()->id,
            403,
            'Vous n\'êtes pas autorisé à supprimer ce modèle.'
        );
        Storage::disk('public')->delete($template->file_path);
        $template->delete();

        return response()->json(['message' => 'Modèle supprimé.']);
    }

    public function generate(Request $request, string $id): JsonResponse
    {
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'variables' => ['required', 'array'],
            'format' => ['sometimes', 'string', 'in:pdf,docx,txt'],
        ]);

        $format = $validated['format'] ?? $template->type;
        $outputPath = 'generated/' . uniqid('doc_', true);

        // 1) Récupérer le contenu source (priorité au contenu texte stocké)
        if (!empty($template->content)) {
            $content = $template->content;
        } elseif ($template->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($template->file_path)) {
            $content = \Illuminate\Support\Facades\Storage::disk('public')->get($template->file_path);
        } else {
            $content = '';
        }

        if (trim((string) $content) === '') {
            return response()->json(['message' => 'Fichier template introuvable ou vide.'], 404);
        }

        // 2) Remplacer les variables {{key}} -> valeur (échappées pour HTML/PDF)
        foreach ($validated['variables'] as $key => $value) {
            $content = str_replace(
                '{{' . $key . '}}',
                (string) ($value ?? ''),
                $content
            );
        }

        // 3) Générer le document selon le format demandé
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if ($format === 'pdf') {
            $html = nl2br(e($content));
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $path = $outputPath . '.pdf';
            $disk->put($path, $pdf->output());
        } elseif ($format === 'docx') {
            // DOCX généré via PHPWord : préservation simple du texte avec sauts de ligne.
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();
            foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
                $section->addText($line);
            }
            $path = $outputPath . '.docx';
            $tmp = tempnam(sys_get_temp_dir(), 'afdocx');
            $phpWord->save($tmp, 'Word2007');
            $disk->put($path, file_get_contents($tmp));
            @unlink($tmp);
        } else {
            $path = $outputPath . '.txt';
            $disk->put($path, $content);
        }

        return response()->json([
            'message' => 'Document généré avec succès.',
            'data' => [
                'url' => $disk->url($path),
                'path' => $path,
                'format' => $format,
            ],
        ]);
    }

    public function variables(): JsonResponse
    {
        return response()->json([
            'data' => [
                '{{nom}}' => 'Nom du destinataire',
                '{{prenom}}' => 'Prénom du destinataire',
                '{{fonction}}' => 'Fonction du destinataire',
                '{{direction}}' => 'Direction/Service',
                '{{date}}' => 'Date du document',
                '{{numero}}' => 'Numéro du document',
                '{{objet}}' => 'Objet du document',
                '{{reference}}' => 'Référence du document',
                '{{auteur}}' => 'Auteur du document',
                '{{lieu}}' => 'Lieu d\'émission',
                '{{destinataire}}' => 'Destinataire complet',
                '{{civilite}}' => 'Civilité (M./Mme/Mlle)',
                '{{adresse}}' => 'Adresse du destinataire',
                '{{ville}}' => 'Ville',
                '{{code_postal}}' => 'Code postal',
                '{{pays}}' => 'Pays',
                '{{signataire}}' => 'Nom du signataire',
                '{{titre_document}}' => 'Titre complet du document',
            ],
        ]);
    }
}