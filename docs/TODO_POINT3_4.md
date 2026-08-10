# TODO — Points 3 & 4

## Point 3 — Signature naturelle posée sur le document (overlay)
- [ ] `SignedPdfGenerator.php` : remplacer le « bloc signature séparé » par un overlay absolu positionné aux coordonnées % du placement (image + nom + fonction + date) → aucun ajout de page, aucune casse de mise en page.
- [ ] Vérifier que le document source (PDF/Word) sert de fond et que la signature est posée dessus.

## Point 4 — Capture signature manuscrite par webcam (scanner)
- [ ] Créer `CameraSignatureCapture.vue` : accès webcam, recadrage auto, fond transparent, enregistrement PNG.
- [ ] Intégrer le mode « Scanner » dans `SignatureList.vue` (3e mode à côté de Dessiner / Importer).
- [ ] Build `npm run build` validé.
