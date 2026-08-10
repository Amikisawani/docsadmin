# TODO — Refonte DocsAdmin Version Présidence (Directeur de Cabinet)

## Phase 1 : Backend — Enforcer le pouvoir de signature unique
- [x] Analyser l'architecture existante
- [x] `User::canSignDocuments()` et `signingRoles()` → uniquement `directeur_cabinet` (déjà en place)
- [x] `SignDocumentUseCase` guard mis à jour (seul directeur_cabinet via `canSignDocuments()`)
- [ ] Ajouter un utilisateur `directeur_cabinet` dans le seeder

## Phase 2 : Backend — PDF signé propre (aucune mention technique visible)
- [x] Retirer le bandeau « verif » (hash/signataire/date) de `SignedPdfGenerator` (déjà en place)
- [x] Les métadonnées techniques restent dans `DocumentSignature` + logs d'audit

## Phase 3 : Frontend — Navigation par rôle dans `AdminLayout.vue`
- [ ] Directeur de Cabinet → boîte de validation uniquement
- [ ] Utilisateurs standards → masquer Signature / Tâches / Gestion de signatures
- [ ] Admin → voit tout

## Phase 4 : Frontend — Routage post-connexion
- [ ] Rediriger le Directeur de Cabinet vers `/director` après connexion

## Phase 5 : Frontend — Actions document pour utilisateurs standards
- [ ] `DocumentShow.vue` : bouton « Envoyer à la signature » (priorité + délai)
- [ ] Bouton « Rappeler la demande de signature » (auteur, si pending)
- [ ] Masquer « Signer » pour les rôles non-directeur

## Phase 6 : Notifications temps réel
- [ ] Événement Reverb « nouveau document soumis »
- [ ] Souscription Echo côté frontend
- [ ] Mise à jour auto de la boîte du Directeur

## Phase 7 : Finitions UI
- [ ] Cohérence visuelle du tableau de bord Directeur
- [ ] Vérification build (`npm run build`) et tests (`php artisan test`)
