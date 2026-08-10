# TODO — Complétion refonte DocsAdmin Version Présidence (Directeur de Cabinet)

## Backend — En place (vérifié)
- [x] `User::signingRoles()` → uniquement `directeur_cabinet` ; `canSignDocuments()` l'applique
- [x] `SignDocumentUseCase` utilise `canSignDocuments()`
- [x] Migration `2026_08_03_000001_add_signature_workflow_to_documents` (priority, deadline, rejection_reason, recalled_at, submitted_for_signature_at)
- [x] Use cases servant le workflow : `SubmitForSignatureUseCase`, `RecallSignatureUseCase`, `RejectForSignatureUseCase`
- [x] Événements de diffusion : `DocumentSubmitted`, `DocumentSigned`, `DocumentRejected`, `DocumentRecalled`
- [x] Contrôleurs : `DirectorInboxController`, `DocumentController` (submit/recall/reject/sign), `AuthController` pending count
- [x] Routes API : directors, inbox, stats, submit/recall/reject/sign, audit signatures/rejections/director-activity

## Travail en cours / à faire
- [x] Ajout de `laravel-echo` + `pusher-js` dans `package.json`
- [x] Création de `resources/js/echo.ts` (bootstrap temps réel avec repli polling)
- [x] Câblage de `app.ts` au temps réel après authentification
- [x] `notification.ts` : méthode `startRealtime()` (écoute Echo)
- [ ] **AdminLayout** : navigation par rôle (Directeur = boîte uniquement ; utilisateurs standards masquent Signature/Tâches/Gestion signatures ; admin = tout)
- [ ] **DocumentShow** : boutons « Envoyer à la signature » (priorité + délai) et « Rappeler » pour les utilisateurs standards ; masquer « Signer » pour non-directeur
- [ ] **DocumentList** : boutons « Envoyer à la signature » / « Rappeler » pour les documents de l'utilisateur
- [ ] **Seeder** : ajouter un utilisateur `directeur_cabinet`
- [ ] Installer les dépendances JS (`npm install`)
- [ ] Vérifier le build (`npm run build`) et les tests (`php artisan test`)
