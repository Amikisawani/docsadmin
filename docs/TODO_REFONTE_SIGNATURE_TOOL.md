# TODO — Correction & extension de SignatureTool.vue (Version Présidence)

## Objectif
Le Directeur de Cabinet est le SEUL détenteur du pouvoir de signature.
`SignatureTool.vue` doit refléter cette règle (rôle + endpoint) et être pleinement fonctionnel.

## Étapes
- [x] Analyser l'existant (backend + frontend)
- [x] Vérifier l'endpoint backend : `POST /documents/{id}/sign` (signByDirector)
- [x] Vérifier le rôle signataire : `directeur_cabinet`
- [x] Corriger `roleDenied` : utiliser `directeur_cabinet` (et admin) au lieu des anciens rôles
- [x] Corriger `sign()` : pointer vers `POST /documents/{id}/sign` (endpoint `signByDirector`)
- [x] Ajouter un garde-fou : document réellement en attente de signature (status `pending`) → `documentSignable`
- [x] Améliorer la détection du publipostage (utiliser `flow_type`/`is_mail_merge` du document)
- [x] Mettre à jour tous les commentaires internes (règle métier Présidence)
- [x] Gestion d'erreur plus robuste (message clair en cas de document non envoyé / déjà signé)
- [x] Vérifier le build frontend (`npm run build`)
