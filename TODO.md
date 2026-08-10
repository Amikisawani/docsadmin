# TODO — Refonte Présidence (Phase finale)

## 1. Configuration Broadcasting
- [x] Créer `config/broadcasting.php` (driver depuis `BROADCAST_CONNECTION`)
- [x] Vérifier `.env` a `BROADCAST_CONNECTION=log` ou `pusher`/`reverb`

## 2. Types Vite
- [x] Créer `resources/js/vite-env.d.ts` pour `import.meta.env`

## 3. Nettoyage PDF signé
- [x] `SignedPdfGenerator.php` : retirer la mention de vérification (nom, date, hash) — ✅ déjà fait
- [x] Ne garder que la signature naturelle sur le document

## 4. Rework DocumentShow.vue
- [ ] Bouton « Envoyer à la signature » pour l'auteur (draft/rejected)
- [ ] Bouton « Rappeler » pour l'auteur (pending/non signé)
- [ ] Bouton « Signer » réservé au `directeur_cabinet`
- [ ] Afficher priorité / délai / motif de rejet

## 5. Garde de visibilité show()
- [ ] `DocumentController::show()` : utilisateur standard ne voit que ses documents
- [ ] Directeur de Cabinet utilise l'inbox (pas show direct)

## 6. Types frontend
- [ ] Ajouter les champs `priority`, `deadline`, `submitted_for_signature_at`, `rejection_reason`, `recalled_at` dans `types/index.ts`

## 7. Vérification finale
- [ ] `php artisan config:clear && php artisan migrate`
- [ ] `npm run build`
