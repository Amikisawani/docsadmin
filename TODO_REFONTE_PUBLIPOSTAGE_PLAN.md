# Plan d'exécution — Refonte Publipostage (Version Présidence)

## Objectif
Terminer le flux publipostage simplifié « Présidence » : génération immédiate,
envoi à la signature, et signature d'une campagne par le Directeur de Cabinet.

## Backend (combler les écarts)
- [x] `RunMailMergeUseCase` : génération immédiate + workflow optionnel
  - `execute()` : statut initial `processing`, démarre le workflow uniquement si fourni & document non prêt
  - `resolveSourceContent()` : accepter le document source quel que soit son statut (draft compris)
- [x] `MailMergeController` : `workflow_id` nullable (validation)
- [x] `MailMergeController::authorizeBatch` : autoriser le Directeur de Cabinet à consulter les campagnes `pending_signature`/`signed`/`rejected`
- [x] `DirectorInboxController` : retourner les campagnes `pending_signature` dans `index()` + compteur dans `stats()`

## Frontend
- [x] `types/index.ts` : ajouter `signed` au statut `MailMergeRecipient`
- [x] `stores/directorInbox.ts` : charger les campagnes + action `signCampaign`
- [x] `pages/director/DirectorDashboard.vue` : panneau « Campagnes à signer »
- [x] `pages/director/DirectorDocumentReview.vue` : mode campagne (afficher destinataires + signer)
- [x] `router/index.ts` : route `/director/campaigns/:id` (name `DirectorCampaignReview`, props `mode: 'campaign'`)
- [x] `pages/mailmerge/MailMergeList.vue` : workflow optionnel + nouveaux statuts (pending_signature/signed/rejected) + bouton envoyer à la signature
- [ ] `pages/documents/DocumentForm.vue` : aide variables publipostage

## Vérification
- [x] `npm run build` (build réussi : SignatureTool, DirectorDocumentReview, MailMergeList compilés)
- [ ] Vérifier le flux de bout en bout
