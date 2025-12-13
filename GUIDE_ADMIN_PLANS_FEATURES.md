# 👨‍💼 Guide Admin - Gestion Plans & Features

## 📋 Vue d'ensemble

L'admin peut gérer:
1. **Features** - Les fonctionnalités disponibles (CRUD complet)
2. **Plans** - Les abonnements (CRUD complet)
3. **Association** - Attacher/Détacher des features aux plans

---

## 🔐 Routes Admin

Toutes les routes nécessitent:
- `auth:sanctum` middleware
- `role:admin` middleware (à créer)

**Base URL:** `/api/v1/admin`

---

## 1️⃣ GESTION DES FEATURES

### 📖 Lister toutes les features

```http
GET /api/v1/admin/features
Authorization: Bearer {admin_token}
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "key": "indicateurs_marches",
      "name": "Indicateurs Marchés",
      "slug": "indicateurs-marches",
      "is_active": true,
      "plans_count": 3,
      "created_at": "2025-12-12T10:00:00Z"
    },
    {
      "id": 13,
      "key": "articles_premium",
      "name": "Articles Premium",
      "slug": "articles-premium",
      "is_active": true,
      "plans_count": 1,
      "created_at": "2025-12-12T10:00:00Z"
    }
  ]
}
```

---

### ➕ Créer une nouvelle feature

```http
POST /api/v1/admin/features
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "key": "analyse_technique",
  "name": "Analyse Technique",
  "is_active": true
}
```

**Validation:**
- `key`: requis, unique, format: `a-z_` uniquement
- `name`: requis, max 255 caractères
- `is_active`: optionnel, boolean (default: true)

**Réponse:**
```json
{
  "success": true,
  "message": "Feature créée avec succès.",
  "data": {
    "id": 15,
    "key": "analyse_technique",
    "name": "Analyse Technique",
    "slug": "analyse-technique",
    "is_active": true,
    "created_at": "2025-12-12T15:30:00Z"
  }
}
```

---

### 👁️ Voir une feature et ses plans

```http
GET /api/v1/admin/features/{feature}
Authorization: Bearer {admin_token}
```

**Exemple:** `GET /api/v1/admin/features/13`

**Réponse:**
```json
{
  "success": true,
  "data": {
    "feature": {
      "id": 13,
      "key": "articles_premium",
      "name": "Articles Premium",
      "slug": "articles-premium",
      "is_active": true
    },
    "plans": [
      {
        "id": 3,
        "name": "Premium",
        "slug": "premium",
        "is_enabled": true
      }
    ]
  }
}
```

---

### ✏️ Modifier une feature

```http
PUT /api/v1/admin/features/{feature}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "Articles Premium VIP",
  "is_active": false
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Feature mise à jour avec succès.",
  "data": {
    "id": 13,
    "key": "articles_premium",
    "name": "Articles Premium VIP",
    "is_active": false
  }
}
```

---

### 🗑️ Supprimer une feature

```http
DELETE /api/v1/admin/features/{feature}
Authorization: Bearer {admin_token}
```

**Réponse si la feature est utilisée:**
```json
{
  "success": false,
  "message": "Impossible de supprimer cette feature. Elle est utilisée par 2 plan(s).",
  "hint": "Détachez-la d'abord de tous les plans."
}
```

**Réponse si succès:**
```json
{
  "success": true,
  "message": "Feature supprimée avec succès."
}
```

---

## 2️⃣ GESTION DES PLANS

### 📖 Lister tous les plans avec leurs features

```http
GET /api/v1/admin/plans
Authorization: Bearer {admin_token}
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "key": "pla_abc123",
      "name": "Gratuit",
      "slug": "gratuit",
      "price": 0,
      "billing_cycle": "monthly",
      "trial_days": 0,
      "is_active": true,
      "is_visible": true,
      "sort_order": 1,
      "features_count": 8,
      "features": [
        {
          "id": 1,
          "key": "indicateurs_marches",
          "name": "Indicateurs Marchés",
          "is_enabled": true
        },
        {
          "id": 2,
          "key": "actualites",
          "name": "Actualités",
          "is_enabled": true
        }
      ]
    },
    {
      "id": 3,
      "name": "Premium",
      "slug": "premium",
      "price": 14900,
      "features_count": 14
    }
  ]
}
```

---

### 👁️ Voir un plan spécifique avec toutes ses features

```http
GET /api/v1/admin/plans/{plan}
Authorization: Bearer {admin_token}
```

**Exemple:** `GET /api/v1/admin/plans/3` (Premium)

**Réponse:**
```json
{
  "success": true,
  "data": {
    "plan": {
      "id": 3,
      "key": "pla_xyz789",
      "nom": "Premium",
      "slug": "premium",
      "prix": 14900,
      "billing_cycle": "monthly",
      "description": "Accès illimité à toutes les fonctionnalités premium",
      "trial_days": 14,
      "is_active": true,
      "is_visible": true,
      "sort_order": 3
    },
    "features": [
      {
        "id": 1,
        "key": "indicateurs_marches",
        "name": "Indicateurs Marchés",
        "is_enabled": true
      },
      {
        "id": 13,
        "key": "articles_premium",
        "name": "Articles Premium",
        "is_enabled": true
      }
    ],
    "subscriptions_count": 25,
    "active_subscriptions_count": 18
  }
}
```

---

### ➕ Créer un nouveau plan

```http
POST /api/v1/admin/plans
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "nom": "Entreprise",
  "slug": "entreprise",
  "prix": 49900,
  "devise": "XOF",
  "billing_cycle": "monthly",
  "description": "Pour les grandes entreprises",
  "trial_days": 30,
  "sort_order": 4,
  "is_active": true,
  "is_visible": true
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Plan créé avec succès.",
  "data": {
    "id": 4,
    "key": "pla_def456",
    "nom": "Entreprise",
    "slug": "entreprise",
    "prix": 49900,
    "features_count": 0
  }
}
```

---

### ✏️ Modifier un plan

```http
PUT /api/v1/admin/plans/{plan}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "prix": 39900,
  "description": "Nouveau tarif promotionnel"
}
```

---

### 🗑️ Supprimer un plan

```http
DELETE /api/v1/admin/plans/{plan}
Authorization: Bearer {admin_token}
```

**Réponse si des souscriptions actives existent:**
```json
{
  "success": false,
  "message": "Impossible de supprimer ce plan. 15 souscription(s) active(s) l'utilisent."
}
```

---

## 3️⃣ ASSOCIATION PLAN ↔ FEATURES

### ➕ Attacher des features à un plan

```http
POST /api/v1/admin/plans/{plan}/features
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "features": [
    {
      "feature_id": 1,
      "is_enabled": true
    },
    {
      "feature_id": 2,
      "is_enabled": true
    },
    {
      "feature_id": 15,
      "is_enabled": false
    }
  ]
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Features attachées avec succès.",
  "data": {
    "id": 1,
    "name": "Gratuit",
    "features": [
      {
        "id": 1,
        "key": "indicateurs_marches",
        "is_enabled": true
      },
      {
        "id": 2,
        "key": "actualites",
        "is_enabled": true
      }
    ]
  }
}
```

---

### 🗑️ Détacher des features d'un plan

```http
DELETE /api/v1/admin/plans/{plan}/features
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "feature_ids": [15, 16]
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Features détachées avec succès.",
  "data": {
    "features_count": 12
  }
}
```

---

### ✏️ Modifier une feature spécifique d'un plan

```http
PATCH /api/v1/admin/plans/{plan}/features/{feature}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "is_enabled": false
}
```

**Exemple:** `PATCH /api/v1/admin/plans/1/features/13`

**Réponse:**
```json
{
  "success": true,
  "message": "Feature mise à jour avec succès.",
  "data": [
    {
      "id": 13,
      "key": "articles_premium",
      "is_enabled": false
    }
  ]
}
```

---

### 👁️ Activer/Désactiver visibilité d'un plan

```http
POST /api/v1/admin/plans/{plan}/toggle-visibility
Authorization: Bearer {admin_token}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Plan masqué.",
  "data": {
    "is_visible": false
  }
}
```

---

## 🔄 WORKFLOW ADMIN COMPLET

### Scénario 1: Créer un nouveau plan "Entreprise" avec des features

```bash
# Étape 1: Créer le plan
POST /api/v1/admin/plans
{
  "nom": "Entreprise",
  "slug": "entreprise",
  "prix": 49900
}
# → Réponse: plan_id = 4

# Étape 2: Attacher toutes les features
POST /api/v1/admin/plans/4/features
{
  "features": [
    {"feature_id": 1, "is_enabled": true},
    {"feature_id": 2, "is_enabled": true},
    // ... toutes les 14 features
  ]
}

# Étape 3: Vérifier le plan
GET /api/v1/admin/plans/4
```

---

### Scénario 2: Ajouter une nouvelle feature "Analyse IA"

```bash
# Étape 1: Créer la feature
POST /api/v1/admin/features
{
  "key": "analyse_ia",
  "name": "Analyse IA"
}
# → Réponse: feature_id = 15

# Étape 2: L'attacher au plan Premium
POST /api/v1/admin/plans/3/features
{
  "features": [
    {"feature_id": 15, "is_enabled": true}
  ]
}

# Étape 3: Vérifier
GET /api/v1/admin/plans/3
```

---

### Scénario 3: Désactiver temporairement une feature

```bash
# Désactiver "articles_premium" pour le plan Pro
PATCH /api/v1/admin/plans/2/features/13
{
  "is_enabled": false
}

# Les users du plan Pro n'auront plus accès à cette feature
```

---

### Scénario 4: Retirer une feature de tous les plans avant suppression

```bash
# Étape 1: Vérifier quels plans utilisent la feature
GET /api/v1/admin/features/15

# Réponse montre: plans [2, 3, 4]

# Étape 2: Détacher de chaque plan
DELETE /api/v1/admin/plans/2/features
{"feature_ids": [15]}

DELETE /api/v1/admin/plans/3/features
{"feature_ids": [15]}

DELETE /api/v1/admin/plans/4/features
{"feature_ids": [15]}

# Étape 3: Supprimer la feature
DELETE /api/v1/admin/features/15
```

---

## 📁 Fichiers des Controllers

### FeatureManagementController
**Fichier:** `app/Http/Controllers/Api/V1/Admin/FeatureManagementController.php`

**Méthodes:**
- `index()` - Liste toutes les features
- `store()` - Créer une feature
- `show()` - Voir une feature + plans
- `update()` - Modifier une feature
- `destroy()` - Supprimer une feature

### PlanManagementController
**Fichier:** `app/Http/Controllers/Api/V1/Admin/PlanManagementController.php`

**Méthodes:**
- `index()` - Liste tous les plans
- `store()` - Créer un plan
- `show()` - Voir un plan + features + stats
- `update()` - Modifier un plan
- `destroy()` - Supprimer un plan
- `attachFeatures()` - Attacher features
- `detachFeatures()` - Détacher features
- `updateFeature()` - Modifier une feature du plan
- `toggleVisibility()` - Masquer/Afficher plan

---

## 🔗 Routes à ajouter

Dans `routes/subscription.php` ou `routes/api.php`:

```php
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // Features CRUD
    Route::apiResource('features', FeatureManagementController::class);

    // Plans CRUD
    Route::apiResource('plans', PlanManagementController::class);

    // Association Plan ↔ Features
    Route::post('plans/{plan}/features', [PlanManagementController::class, 'attachFeatures']);
    Route::delete('plans/{plan}/features', [PlanManagementController::class, 'detachFeatures']);
    Route::patch('plans/{plan}/features/{feature}', [PlanManagementController::class, 'updateFeature']);

    // Actions spéciales
    Route::post('plans/{plan}/toggle-visibility', [PlanManagementController::class, 'toggleVisibility']);

});
```

---

## 🎯 Résumé des endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| **FEATURES** |
| GET | `/admin/features` | Liste toutes les features |
| POST | `/admin/features` | Créer une feature |
| GET | `/admin/features/{id}` | Voir feature + plans |
| PUT | `/admin/features/{id}` | Modifier feature |
| DELETE | `/admin/features/{id}` | Supprimer feature |
| **PLANS** |
| GET | `/admin/plans` | Liste tous les plans |
| POST | `/admin/plans` | Créer un plan |
| GET | `/admin/plans/{id}` | Voir plan + features |
| PUT | `/admin/plans/{id}` | Modifier plan |
| DELETE | `/admin/plans/{id}` | Supprimer plan |
| **ASSOCIATION** |
| POST | `/admin/plans/{id}/features` | Attacher features |
| DELETE | `/admin/plans/{id}/features` | Détacher features |
| PATCH | `/admin/plans/{id}/features/{fid}` | Modifier feature |
| POST | `/admin/plans/{id}/toggle-visibility` | Visibilité |

---

## ✅ Checklist Admin

- [ ] Créer le middleware `role:admin`
- [ ] Ajouter les routes admin dans `routes/subscription.php`
- [ ] Tester la création de feature
- [ ] Tester la création de plan
- [ ] Tester l'attachement de features
- [ ] Tester le détachement de features
- [ ] Vérifier les permissions admin

**L'admin a maintenant un contrôle total sur Plans & Features!** 🎉
