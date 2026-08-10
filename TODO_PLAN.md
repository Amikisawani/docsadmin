# Plan — Séparation documents uniques / publipostage

## Contexte
Le module « Modèle » est redondant avec le document. On le masque (pas de suppression pour éviter de casser).
On introduit `flow_type` sur `documents` : `unique` (workflow obligatoire jusqu'à la signature) ou `mail_merge` (destiné à être multiplié, visible dans le formulaire de publipostage).
Le formulaire de publipostage demande son propre workflow de validation jusqu'à la signature.

## Étapes

### Backend — Documents
- [x] Créer migration `add_flow_type_to_documents` (flow_type enum unique/mail_merge, défaut unique, is_mail_merge boolean dérivé)
- [x] Modèle `Document` : fillable + accesseur `is_mail_merge` + scope
- [x] `CreateDocumentAction` : workflow optionnel (unique → requis, mail_merge → non)
- [x] `DocumentController@store` : validation conditionnelle workflow_id required_if unique
- [x] `DocumentController` : adapter `index` et `show` pour exposer flow_type

### Backend — Publipostage
- [x] Migration ajouter workflow_id + current_workflow_instance_id + status workflow sur mail_merge_batches
- [x] Modèle `MailMergeBatch` : relations + fillable
- [x] `MailMergeController@documents` : ne lister que flow_type=mail_merge éligibles
- [x] `MailMergeController@store` : retirer template_id, retirer source_file, exiger workflow_id
- [x] `RunMailMergeUseCase` : supprimer branche template + source_file ; démarrer le workflow du batch avant génération
- [x] `RunMailMergeUseCase` : pré-remplir variables depuis le document

### Frontend
- [x] `DocumentForm.vue` : sélecteur « Type de flux » (unique / publipostage), workflow requis si unique
- [x] `DocumentList.vue` / `DocumentShow.vue` : badge « Publipostage » / « Unique »
- [x] `MailMergeList.vue` : retirer onglet « Modèle » et fichier source ; ajouter workflow requis ; ne lister que documents mail_merge
- [x] `AdminLayout.vue` : masquer le menu « Modèles »
- [x] `types/index.ts` : ajouter flow_type à Document

### Tests
- [x] Mettre à jour `MailMergeTest` (workflow batch requis, pas de template)
- [x] Mettre à jour tests création document (workflow requis unique / optionnel mail_merge)
- [x] `php artisan migrate`
- [x] `php artisan test` (suite complète verte)
- [x] `npm run build` (build frontend vert)

## Suivi
- [x] MAJ TODO.md (Phase F)

