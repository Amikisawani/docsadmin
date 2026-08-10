# TODO — Fix « nouvelle signature absente du formulaire Signer le document »

## Cause racine
`SignatureTool.vue` référence `canSign` dans le template (`v-if="!canSign"`) mais ne le définit
nulle part dans `<script setup>`. En Vue 3, `!undefined === true` → le bloc
« Aucune signature active disponible » s'affiche toujours et le formulaire (avec la liste
déroulante des signatures) est masqué en permanence.

## Corrections appliquées
1. `canSign` est maintenant un `computed` = `signatures.value.length > 0 && !roleDenied.value`
   (l'utilisateur voit le formulaire s'il a au moins une signature active ET un rôle signataire).
2. Ajout d'un état `loading` (`ref(true)`) avec un bloc « Chargement des signatures... »
   pour éviter le clignotement du message vide pendant le fetch.
3. `loading` passe à `false` dans le `finally` du `onMounted`.
4. Le `onMounted` charge désormais `/signatures` **sans filtre de type** (`?type=graphical` retiré)
   afin que toutes les signatures (graphical, digital, certificate) apparaissent, y compris les nouvelles.
5. Les signatures inactives restent filtrées côté client (`is_active !== false`).
6. Build frontend `npm run build` réussi (vite v8, built in 8.74s, aucune erreur).

## Résultat
À l'ouverture du formulaire « Signer le document », l'utilisateur connecté avec un rôle signataire
(admin, secretaire_general, directeur, chef_division, chef_bureau) voit maintenant la liste de ses
signatures dans le menu déroulant, y compris la nouvelle signature récemment ajoutée.

