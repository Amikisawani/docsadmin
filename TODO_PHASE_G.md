# TODO — Phase G : Suivi workflow, notifications en temps réelle & rappels

## G1 — Suivi de progression du workflow
- [x] `DocumentController::workflowProgress()` — endpoint `GET /documents/{document}/workflow-progress`
- [x] `MailMergeController::workflowProgress()` — endpoint `GET /mail-merge/{batch}/workflow-progress`
- [x] Eager loading `currentWorkflowInstance.approvals.approver` dans `DocumentController@index` et `@show`
- [x] Eager loading `currentWorkflowInstance.approvals.approver` dans `MailMergeController@index` et `@show`
- [x] Interface `WorkflowProgress` ajoutée dans `resources/js/types/index.ts`
- [x] `workflow?` et `currentWorkflowInstance?` ajoutés à l'interface `Document`
- [x] `document_id`, `document?`, `workflow_id`, `current_workflow_instance_id`, `currentWorkflowInstance?`, `created_by` ajoutés à l'interface `MailMergeBatch`

## G2 — Notifications en temps réel
- [x] Store Pinia `resources/js/stores/notification.ts` — liste, compteur non-lu, polling 30s, `markAsRead`, `markAllAsRead`
- [x] Correction `pollingInterval` : `const` → `let`
- [x] Dropdown notifications dans `AdminLayout.vue` — badge non-lu, liste, bouton « Tout lire », lien « Voir toutes les tâches »
- [x] Outside-click handler pour fermer le dropdown
- [x] Lien « Mes tâches » ajouté à la navigation

## G3 — Rappels aux approbateurs
- [x] `WorkflowController::remind()` — endpoint `POST /workflows/approvals/{approval}/remind`
- [x] Autorisation : auteur du document ou admin
- [x] `NotificationService` injecté dans `WorkflowController`
- [x] `sendReminder()` dans `DocumentList.vue` — trouve l'approbation pending → appelle l'endpoint de rappel
- [x] `remindBatch()` dans `MailMergeList.vue` — même logique pour les campagnes de publipostage

## G4 — Composants de suivi workflow
- [x] `WorkflowProgressCell.vue` — barre de progression, étape courante, approbateur en attente, bouton rappel (visible auteur/admin)
- [x] `BatchWorkflowProgressCell.vue` — même logique pour les objets `MailMergeBatch`
- [x] Colonne « Suivi workflow » ajoutée dans `DocumentList.vue`
- [x] Colonne « Suivi workflow » ajoutée dans `MailMergeList.vue`

## G5 — Page « Mes tâches »
- [x] `MyTasks.vue` — approbations workflow en attente, documents à signer, campagnes publipostage en attente
- [x] Boutons de rappel intégrés
- [x] Route `/tasks` ajoutée dans `router/index.ts`

## G6 — Nettoyage & validation
- [x] Nettoyage des fichiers PHP/Blade mal placés dans `resources/js/pages/archives/` (19 fichiers supprimés)
- [x] Nettoyage des fichiers PHP mal placés dans `routes/` (4 fichiers supprimés)
- [x] Validation TypeScript + build Vite réussis

## G7 — Améliorations de présentation
- [x] CSS : scrollbar horizontale stylée, en-têtes de tableaux adaptatives (sticky, responsive)
- [x] En-tête d'état d'avancement dans chaque tableau (ex: « signé 2 fois, reste 1 fois »)
- [x] Action « Voir » dans MailMergeList avec modal iframe pour prévisualiser les documents générés
- [x] DocumentShow.vue : affichage du document créé (iframe PDF / contenu) + état d'avancement workflow détaillé
- [x] Rebuild & validation finale

