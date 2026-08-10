# TODO — Correctifs UI/UX (nav, débordement tableaux, toggle)

## Objectif
Résoudre les 3 problèmes signalés par l'utilisateur.

## Tâches
- [x] 1. `app.css` : ajouter `min-width:0; max-width:100%` aux conteneurs (page-container, table-card, filter-card, form-card, info-card, main-content) pour empêcher tout débordement à droite.
- [x] 2. `app.css` : rendre `.table-wrapper` scrollable horizontalement ET verticalement (overflow-x auto + overflow-y auto + max-height ~70vh) pour que le tableau s'adapte à la nav (ouverte/fermée) sans déborder de l'écran.
- [x] 3. `AdminLayout.vue` : corriger la position du bouton toggle quand la nav est fermée — le placer à l'emplacement du logo (centré) avec animation (fondu + scale), toujours à l'intérieur de la nav.
- [x] 4. `AdminLayout.vue` : polir les infobulles (tooltips) du menu quand la nav est fermée (chaque lien + bouton déconnexion au survol) et masquer les libellés de sections quand la nav est réduite.
- [x] 5. Vérifier avec `npm run build` — build réussi (✓ built in 5.69s).
