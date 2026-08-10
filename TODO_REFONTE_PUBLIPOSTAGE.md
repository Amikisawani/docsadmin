# Refonte Publipostage — Version Présidence (flux simplifié)

Objectif : simplifier le publipostage conformément au workflow Présidence.

## Flux cible
1. L'agent crée un document **publipostage** contenant des variables `{{...}}`.
2. L'agent ouvre le formulaire Publipostage, choisit le document + liste des agents, valide → **génération immédiate** des PDF (statut `completed`).
3. L'agent clique « Envoyer à la signature » → la **campagne** passe en `pending_signature`, le Directeur est notifié.
4. Le Directeur voit la **campagne** dans sa boîte de réception, l'ouvre et **signe une seule fois**.
5. Sa signature est **recopiée sur chaque PDF** de la campagne.

## Étapes

### Backend
- [x] Créer `app/Application/Signatures/SignMailMergeCampaignUseCase.php`
- [x] Créer `app/Application/Signatures/SendCampaignToSignatureUseCase.php`
- [x] Simplifier `app/Application/MailMerge/RunMailMergeUseCase.php` (génération immédiate, workflow optionnel)
- [x] Étendre `app/Application/Signatures/SignedPdfGenerator.php` (signer un PDF de campagne via contenu)
- [x] Mettre à jour `app/Http/Controllers/Api/MailMergeController.php`
- [x] Étendre `app/Http/Controllers/Api/DirectorInboxController.php` (campagnes `pending_signature`)
- [x] Étendre `app/Domains/MailMerge/Models/MailMergeBatch.php` (nouvelles colonnes signature)
- [x] Migration `2026_08_xx_add_signature_to_mail_merge.php`
- [x] Routes `routes/api.php`

### Frontend
- [ ] Simplifier `resources/js/pages/mailmerge/MailMergeList.vue`
- [ ] Étendre `resources/js/stores/directorInbox.ts`
- [ ] Étendre `resources/js/types/index.ts`
- [ ] Ajouter les campagnes dans `DirectorDashboard.vue`
- [ ] Étendre `DirectorDocumentReview.vue` (signature de campagne)
- [ ] Aide variables dans `DocumentForm.vue`

### Tests / Vérification
- [ ] `npm run build`
- [ ] Vérifier le flux de bout en bout
