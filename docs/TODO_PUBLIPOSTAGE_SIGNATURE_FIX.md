# Plan — Corrections flux Publipostage → Signature (Directeur de Cabinet)

## Objectif
Résoudre les 4 problèmes remontés :
1. Les campagnes générées n'apparaissent pas dans le dashboard du Directeur → **automatiser l'envoi à la signature** (Option A) + boutons manuels « Envoyer / Rappeler ».
2. Le bouton « Signer » est désactivé sans explication dans l'interface du Directeur → message clair + lien vers création de signature.
3. Les documents à signer n'apparaissent dans l'aperçu qu'une fois signés → onglets avant/après.
4. Garder le contrôle manuel (envoyer / rappeler) dans le tableau publipostage.

## Étapes
- [x] **Backend** : Automatiser l'envoi à la signature après génération (sans workflow) dans `RunMailMergeUseCase`.
- [x] **Backend** : Ajouter un endpoint `recall-signature` pour les campagnes (`MailMergeController`).
- [x] **Frontend** `MailMergeList.vue` : boutons « Envoyer à la signature » / « Rappeler la signature » pour toutes les campagnes éligibles (y compris statut `awaiting_workflow` avec documents générés).
- [x] **Frontend** `DirectorDocumentReview.vue` (mode campagne) : correction du bouton « Signer » (message si aucune signature).
- [x] **Frontend** `DirectorDocumentReview.vue` (mode document) : aperçu avant/après signature (onglets).
- [x] Vérifier le build (`npm run build`).

## Corrections supplémentaires appliquées
- `SendCampaignToSignatureUseCase` accepte désormais les campagnes `awaiting_workflow` (documents générés mais en attente de workflow) pour l'envoi manuel à la signature.
- `SignatureTool.vue` : pré-place automatiquement la signature (position par défaut) pour que le bouton « Signer le document » soit immédiatement actif.

## Backend
- `app/Application/MailMerge/RunMailMergeUseCase.php` : après génération `completed`, envoyer automatiquement à la signature (statut `pending_signature`, `submitted_for_signature_at`, notification).
- `app/Http/Controllers/Api/MailMergeController.php` : nouvel endpoint `recallSignature`.

## Frontend
- `resources/js/pages/mailmerge/MailMergeList.vue` : action « Envoyer à la signature » pour `completed`/`recalled`, action « Rappeler » pour `pending_signature`.
- `resources/js/pages/director/DirectorDocumentReview.vue` : gestion « aucune signature » + aperçu avant/après.
- `resources/js/router/index.ts` : route `recall-signature`.
