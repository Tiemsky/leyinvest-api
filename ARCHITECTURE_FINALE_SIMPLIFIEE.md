# 🎯 Architecture Finale Simplifiée - Subscription & Features

## 📋 Structure Minimaliste

### Tables essentielles

```
features (14 lignes)
  ↓
plan_features (table pivot - 34 lignes)
  ↓
plans (3 lignes)
  ↓
subscriptions
  ↓
users
```

---

## 🗄️ Structure des tables

### Table: `features`
```sql
CREATE TABLE features (
    id BIGINT PRIMARY KEY,
    key VARCHAR UNIQUE,         -- "indicateurs_marches", "articles_premium"
    name VARCHAR,               -- "Indicateurs Marchés", "Articles Premium"
    slug VARCHAR UNIQUE,
    is_active BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Table: `plans`
```sql
CREATE TABLE plans (
    id BIGINT PRIMARY KEY,
    key VARCHAR UNIQUE,
    nom VARCHAR,                -- "Gratuit", "Pro", "Premium"
    slug VARCHAR UNIQUE,
    prix DECIMAL(8,2),
    billing_cycle VARCHAR,
    is_active BOOLEAN,
    is_visible BOOLEAN,
    trial_days INTEGER,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### Table: `plan_features` (PIVOT)
```sql
CREATE TABLE plan_features (
    id BIGINT PRIMARY KEY,
    plan_id BIGINT FOREIGN KEY,
    feature_id BIGINT FOREIGN KEY,
    is_enabled BOOLEAN,         -- true/false
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE(plan_id, feature_id)
);
```

---

## 🔄 Flux de seed

### 1. FeatureSeeder (PREMIER)

**Rôle:** Créer 14 features globales

```php
$features = [
    // 8 features gratuites
    ['key' => 'indicateurs_marches', 'name' => 'Indicateurs Marchés'],
    ['key' => 'actualites', 'name' => 'Actualités'],
    ['key' => 'articles_standard', 'name' => 'Articles Standard'],
    ['key' => 'ma_liste', 'name' => 'Ma Liste'],
    ['key' => 'presentation_entreprise', 'name' => 'Présentation Entreprise'],
    ['key' => 'indicateurs_financiers', 'name' => 'Indicateurs Financiers'],
    ['key' => 'calculateur', 'name' => 'Calculateur'],
    ['key' => 'calendrier_dividendes', 'name' => 'Calendrier Dividendes'],

    // 4 features Pro (en plus des gratuites)
    ['key' => 'evaluations', 'name' => 'Évaluations'],
    ['key' => 'indicateurs_complets', 'name' => 'Indicateurs Complets'],
    ['key' => 'historique_entreprise', 'name' => 'Historique Entreprise'],
    ['key' => 'notifications', 'name' => 'Notifications'],

    // 2 features Premium (en plus de tout)
    ['key' => 'articles_premium', 'name' => 'Articles Premium'],
    ['key' => 'prevision_rendement', 'name' => 'Prévision Rendement'],
];

foreach ($features as $featureData) {
    Feature::updateOrCreate(
        ['key' => $featureData['key']],
        [
            'slug' => Str::slug($featureData['key']),
            'is_active' => true,
            'name' => $featureData['name'],
        ]
    );
}
```

**Résultat:** 14 features dans la table `features`

---

### 2. PlanSeeder (DEUXIÈME)

**Rôle:** Créer 3 plans ET attacher les features appropriées

```php
$plans = [
    [
        'nom' => 'Gratuit',
        'slug' => 'gratuit',
        'prix' => 0,
        'features' => [
            'indicateurs_marches' => ['enabled' => true],
            'actualites' => ['enabled' => true],
            // ... 8 features au total
        ]
    ],
    [
        'nom' => 'Pro',
        'slug' => 'pro',
        'prix' => 11900,
        'features' => [
            // ... 8 features gratuites
            'evaluations' => ['enabled' => true],
            'indicateurs_complets' => ['enabled' => true],
            // ... 12 features au total
        ]
    ],
    [
        'nom' => 'Premium',
        'slug' => 'premium',
        'prix' => 14900,
        'features' => [
            // ... 12 features du Pro
            'articles_premium' => ['enabled' => true],
            'prevision_rendement' => ['enabled' => true],
            // ... 14 features au total
        ]
    ]
];

foreach ($plans as $planData) {
    $features = $planData['features'];
    unset($planData['features']);

    // Créer le plan
    $plan = Plan::updateOrCreate(['slug' => $planData['slug']], $planData);

    // Attacher les features
    foreach ($features as $featureKey => $config) {
        $feature = Feature::where('key', $featureKey)->first();
        if ($feature) {
            $plan->features()->syncWithoutDetaching([
                $feature->id => ['is_enabled' => $config['enabled']]
            ]);
        }
    }
}
```

**Résultat:**
- 3 plans dans `plans`
- 34 liens dans `plan_features` (8 + 12 + 14)

---

## 📊 Répartition des features par plan

| Feature | Gratuit | Pro | Premium |
|---------|---------|-----|---------|
| indicateurs_marches | ✅ | ✅ | ✅ |
| actualites | ✅ | ✅ | ✅ |
| articles_standard | ✅ | ✅ | ✅ |
| ma_liste | ✅ | ✅ | ✅ |
| presentation_entreprise | ✅ | ✅ | ✅ |
| indicateurs_financiers | ✅ | ✅ | ✅ |
| calculateur | ✅ | ✅ | ✅ |
| calendrier_dividendes | ✅ | ✅ | ✅ |
| **evaluations** | ❌ | ✅ | ✅ |
| **indicateurs_complets** | ❌ | ✅ | ✅ |
| **historique_entreprise** | ❌ | ✅ | ✅ |
| **notifications** | ❌ | ✅ | ✅ |
| **articles_premium** | ❌ | ❌ | ✅ |
| **prevision_rendement** | ❌ | ❌ | ✅ |
| **TOTAL** | **8** | **12** | **14** |

---

## 🔍 Comment récupérer les features d'un plan

### Via Eloquent

```php
// Méthode 1: Toutes les features actives d'un plan
$plan = Plan::find(1); // Gratuit
$features = $plan->activeFeatures; // Collection de 8 features

// Méthode 2: Vérifier si un plan a une feature
$plan->hasFeature('articles_premium'); // false pour Gratuit

// Méthode 3: Liste des noms de features
$plan->activeFeatures->pluck('name');
// ["Indicateurs Marchés", "Actualités", ...]
```

### Via SQL

```sql
-- Features du plan Gratuit (id = 1)
SELECT f.key, f.name
FROM features f
INNER JOIN plan_features pf ON f.id = pf.feature_id
WHERE pf.plan_id = 1
  AND pf.is_enabled = 1
  AND f.is_active = 1;
```

---

## 👤 Comment un User accède aux features

```php
// User → Subscription → Plan → Features
$user = User::find(1);

// Via le plan actuel
$currentPlan = $user->currentPlan(); // activeSubscription->plan
$features = $currentPlan->activeFeatures;

// Vérification directe
$user->hasFeature('articles_premium');
// Internally: $this->activeSubscription->plan->hasFeature('articles_premium')

// Protection de route
Route::middleware(['auth:sanctum', 'subscription.feature:articles_premium'])
    ->get('/premium-articles', [ArticleController::class, 'premium']);
```

---

## 🚀 Commandes de seed

```bash
# Ordre obligatoire:
php artisan db:seed --class=FeatureSeeder    # 1. Features d'abord
php artisan db:seed --class=PlanSeeder       # 2. Plans + attachement
```

---

## 📈 Requêtes utiles

### Lister tous les plans avec nombre de features

```php
Plan::withCount('features')->get()->map(function($plan) {
    return [
        'plan' => $plan->nom,
        'prix' => $plan->prix,
        'features_count' => $plan->features_count
    ];
});
```

### Trouver tous les plans ayant une feature spécifique

```php
$feature = Feature::where('key', 'articles_premium')->first();
$plansWithFeature = $feature->plans()->pluck('nom');
// ["Premium"]
```

### Features exclusives à Premium

```sql
SELECT f.name
FROM features f
INNER JOIN plan_features pf ON f.id = pf.feature_id
WHERE pf.plan_id = 3  -- Premium
  AND f.id NOT IN (
      SELECT feature_id FROM plan_features WHERE plan_id IN (1, 2)
  );
-- Résultat: "Articles Premium", "Prévision Rendement"
```

---

## ✅ Architecture finale

```
┌────────────────────────────────────────────────────┐
│  User (Utilisateur)                                │
└────────────────┬───────────────────────────────────┘
                 │
                 │ has one
                 ↓
┌────────────────────────────────────────────────────┐
│  Subscription (Abonnement actif)                   │
│  - status: active                                  │
│  - plan_id → 1, 2 ou 3                            │
└────────────────┬───────────────────────────────────┘
                 │
                 │ belongs to
                 ↓
┌────────────────────────────────────────────────────┐
│  Plan (Gratuit, Pro ou Premium)                    │
│  - id: 1, 2, 3                                    │
└────────────────┬───────────────────────────────────┘
                 │
                 │ has many through plan_features
                 ↓
┌────────────────────────────────────────────────────┐
│  Features (8, 12 ou 14 selon le plan)             │
│  - indicateurs_marches                             │
│  - actualites                                      │
│  - articles_premium (Premium uniquement)           │
└────────────────────────────────────────────────────┘
```

**Simple, scalable, efficace! 🎯**
