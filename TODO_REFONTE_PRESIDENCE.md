# TODO – Refonte Présidence (suivi d'exécution)

Objectif : « Seul le Directeur de Cabinet dispose du pouvoir de signature. »
Corriger les incohérences qui bloquent l'envoi à la signature (agents) et
la lecture/signature côté Directeur de Cabinet.

## Plan approuvé

### Phase 1 – SignatureTool.vue (frontend)
- [ ] `roleDenied` basé uniquement sur `directeur_cabinet`
- [ ] `sign()` → POST `/documents/{id}/sign` (au lieu de `/signatures/sign-document`)
- [ ] Mettre à jour textes/commentaires obsolètes (liste des rôles autorisés)

### Phase 2 – Backend signatures
- [ ] `SignatureController::canSign()` → refléter `directeur_cabinet` seul

### Phase 3 – Base de données / rôles
- [ ] Vérifier `directeur_cabinet` dans `RoleAndPermissionSeeder`
- [ ] Vérifier la présence d'un utilisateur `directeur_cabinet` (DatabaseSeeder)

### Phase 4 – Tests
- [ ] Mettre à jour `SignatureAuthorizationTest.php`
- [ ] Mettre à jour `WorkflowSignatureAuthorizationTest.php`

### Phase 5 – Vérification
- [ ] `php artisan route:list`
- [ ] `npm run build`
- [ ] Relecture des flux agent → DirCab

## Notions de sécurité
- Seul `directeur_cabinet` signe (frontend ET backend via `canSignDocuments()`).
- L'administrateur est supervision-only (ne signe pas).
- Les informations techniques de signature restent dans les journaux d'audit,
  jamais sur le document PDF final.

