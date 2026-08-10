# TODO — Implémentation améliorations espace DirCab

## Objectif
Corriger les 4 points demandés dans l'espace Directeur de Cabinet.

## Étapes
- [x] 1. Réparer `resources/js/stores/directorInbox.ts` (bug syntaxique `fetchHistory` incomplet → casse le store)
- [x] 2. `DirectorDocumentReview.vue` : afficher le texte Word extrait avant signature (pas d'iframe pour .docx)
- [x] 3. Stopper le téléchargement automatique du document Word à signer (bouton de téléchargement explicite uniquement)
- [x] 4. Vérifier le filtre campagnes dans `DirectorInboxController::index()` (OK — cause racine = store cassé)
- [x] 5. Ajouter l'onglet Historique dans `DirectorDashboard.vue` (consomme `/director/history`)
- [x] 6. Build (`npm run build`) — succès (manifest généré dans `public/build`)
- [x] 7. Migration DB `2026_08_04_000001_add_signature_to_mail_merge_batches` appliquée (colonne `submitted_for_signature_at` manquante → erreur SQL du formulaire publipostage corrigée)
