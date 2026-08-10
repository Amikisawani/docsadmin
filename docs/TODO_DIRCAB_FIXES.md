# TODO — Corrections espace Directeur de Cabinet (DirCab)

## Objectif
1. **Aperçu document** : afficher le contenu du document (HTML) au lieu d'une iframe vide/téléchargement auto pour les fichiers Word (.docx/.doc). L'iframe reste pour les PDF.
2. **Campagnes publipostage** : les faire arriver automatiquement dans le dashboard du DirCab (auto-envoi à `pending_signature` après génération, avec ou sans workflow).
3. **Onglet Historique** : activité du DirCab (documents signés, rejetés, rappelés, campagnes).
4. **Aperçu campagne** : rendre le contenu si .docx, sinon iframe pour PDF.

## Étapes
- [ ] Backend `RunMailMergeUseCase.php` : auto-envoi à la signature après génération (avec ou sans workflow).
- [ ] Backend `DirectorInboxController.php` : élargir la requête campagnes + endpoint `history`.
- [ ] Frontend `DirectorDocumentReview.vue` : aperçu HTML du document (Word) + lien téléchargement.
- [ ] Frontend `DirectorDashboard.vue` : ajouter onglet Historique.
- [ ] Store `directorInbox.ts` : fetch history.
- [ ] Vérifier le build (`npm run build`) + lint PHP.
