# 🔐 Guide de Migration - Refresh Tokens avec HTTP-Only Cookies

## 📋 Vue d'ensemble

Cette migration sécurise l'API contre les **attaques XSS** en déplaçant le `refresh_token` des réponses JSON vers des cookies HTTP-only sécurisés.

### ✅ Avant (Vulnérable)
```javascript
// ❌ Token exposé au JavaScript - VULNÉRABLE aux attaques XSS
localStorage.setItem('refresh_token', response.data.refresh_token);
```

### ✅ Après (Sécurisé)
```javascript
// ✅ Token stocké dans un cookie HTTP-only - PROTÉGÉ contre XSS
// Le navigateur gère automatiquement le cookie
```

---

## 🚀 Migration Frontend

### 1️⃣ **Login - Réception du Refresh Token**

#### Avant (Ancien code)
```javascript
// ❌ ANCIEN - NE PLUS UTILISER
const response = await fetch('/api/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password })
});

const data = await response.json();
localStorage.setItem('access_token', data.data.access_token);
localStorage.setItem('refresh_token', data.data.refresh_token); // ❌ Vulnérable
```

#### Après (Nouveau code)
```javascript
// ✅ NOUVEAU - SÉCURISÉ
const response = await fetch('/api/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include', // ⚠️ IMPORTANT : Envoie et reçoit les cookies
  body: JSON.stringify({ email, password })
});

const data = await response.json();
localStorage.setItem('access_token', data.data.access_token);
// ✅ refresh_token est maintenant dans un cookie HTTP-only (automatique)
```

---

### 2️⃣ **Refresh Token - Lecture depuis les Cookies**

#### Avant (Ancien code)
```javascript
// ❌ ANCIEN - NE PLUS UTILISER
const refreshToken = localStorage.getItem('refresh_token');

const response = await fetch('/api/v1/auth/refresh-token', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ refresh_token: refreshToken })
});
```

#### Après (Nouveau code)
```javascript
// ✅ NOUVEAU - SÉCURISÉ
const response = await fetch('/api/v1/auth/refresh-token', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include' // ⚠️ IMPORTANT : Le cookie est envoyé automatiquement
  // Pas besoin de body JSON, le cookie est lu côté serveur
});

const data = await response.json();
localStorage.setItem('access_token', data.data.access_token);
// Le nouveau refresh_token est automatiquement mis à jour dans le cookie
```

---

### 3️⃣ **Logout - Invalidation des Cookies**

#### Avant (Ancien code)
```javascript
// ❌ ANCIEN - NE PLUS UTILISER
await fetch('/api/v1/auth/logout', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  }
});

localStorage.removeItem('access_token');
localStorage.removeItem('refresh_token');
```

#### Après (Nouveau code)
```javascript
// ✅ NOUVEAU - SÉCURISÉ
await fetch('/api/v1/auth/logout', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  },
  credentials: 'include' // ⚠️ IMPORTANT : Le cookie est invalidé côté serveur
});

localStorage.removeItem('access_token');
// Le cookie refresh_token est automatiquement invalidé par le serveur
```

---

## 🔧 Configuration Frontend

### Axios (Configuration globale)
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  withCredentials: true, // ⚠️ CRITIQUE : Active l'envoi des cookies
  headers: {
    'Content-Type': 'application/json'
  }
});

export default api;
```

### Fetch API (Configuration par requête)
```javascript
const response = await fetch('/api/v1/auth/login', {
  method: 'POST',
  credentials: 'include', // ⚠️ Ajouter à CHAQUE requête
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(data)
});
```

---

## ⚙️ Configuration Backend (.env)

Assurez-vous que ces variables sont configurées :

```env
# URL du frontend (CRITIQUE pour CORS)
FRONTEND_URL=http://localhost:3000

# URL de l'application
APP_URL=http://localhost:8000

# Durées de vie des tokens (en minutes)
SANCTUM_ACCESS_TOKEN_EXPIRATION=15      # 15 minutes
SANCTUM_REFRESH_TOKEN_EXPIRATION=10080  # 7 jours

# Environnement (production pour HTTPS strict)
APP_ENV=local
```

---

## 🛡️ Sécurité - Points Importants

### 1. **Cookies HTTP-only**
✅ **Avantages :**
- Inaccessibles au JavaScript (protection XSS)
- Envoyés automatiquement par le navigateur
- Flag `Secure` en production (HTTPS uniquement)
- Flag `SameSite=Strict` (protection CSRF)

### 2. **CORS Configuration**
⚠️ **Prérequis :**
- `credentials: 'include'` dans les requêtes frontend
- `supports_credentials: true` dans `config/cors.php`
- Origins spécifiques (jamais `*` avec credentials)

### 3. **Rate Limiting**
Les endpoints sensibles ont des limites strictes :
- **Login :** 5 tentatives/minute
- **Refresh Token :** 10 tentatives/minute
- **Forgot Password :** 3 tentatives/minute
- **OTP Verification :** 5 tentatives/minute

---

## 🧪 Tests de Migration

### Tester le Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  -c cookies.txt \
  -v
```

Vérifier que le header `Set-Cookie` contient :
```
Set-Cookie: refresh_token=...; HttpOnly; Path=/; SameSite=strict
```

### Tester le Refresh Token
```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh-token \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -c cookies.txt \
  -v
```

Le refresh token doit être lu depuis le cookie (pas de body JSON).

---

## 📊 Réponses API - Changements

### Login Response (Avant)
```json
{
  "success": true,
  "data": {
    "user": {...},
    "access_token": "1|xxx...",
    "refresh_token": "abc123...",  // ❌ Plus présent
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

### Login Response (Après)
```json
{
  "success": true,
  "data": {
    "user": {...},
    "access_token": "1|xxx...",
    // refresh_token maintenant dans le cookie HTTP-only
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

---

## ⚠️ Compatibilité Temporaire

Le backend supporte **temporairement** les deux méthodes pendant la migration :

1. **Cookies HTTP-only** (recommandé et sécurisé)
2. **JSON body** (déprécié, génère un warning dans les logs)

```php
// Le backend accepte encore le body JSON (temporaire)
if (!$refreshToken && $request->has('refresh_token')) {
    $refreshToken = $request->input('refresh_token');
    \Log::warning('Refresh token via JSON body (déprécié)');
}
```

**⏰ Cette compatibilité sera retirée dans une version future.**

---

## 🐛 Troubleshooting

### Problème : "Refresh token manquant"
**Cause :** `credentials: 'include'` non configuré
**Solution :** Ajouter `credentials: 'include'` à toutes les requêtes

### Problème : Cookie non envoyé
**Cause :** CORS mal configuré
**Solution :** Vérifier `FRONTEND_URL` dans `.env` et `config/cors.php`

### Problème : Cookie non reçu
**Cause :** Domaines différents sans configuration CORS
**Solution :**
- Frontend et backend doivent être sur le même domaine OU
- Configurer `SANCTUM_STATEFUL_DOMAINS` correctement

---

## 📝 Checklist de Migration

- [ ] Ajouter `credentials: 'include'` à toutes les requêtes auth
- [ ] Supprimer `localStorage.setItem('refresh_token', ...)`
- [ ] Supprimer `localStorage.getItem('refresh_token')`
- [ ] Configurer `FRONTEND_URL` dans `.env`
- [ ] Tester login/refresh/logout en local
- [ ] Vérifier les cookies dans DevTools (Application > Cookies)
- [ ] Tester en production avec HTTPS

---

## 📞 Support

Pour toute question ou problème, consultez :
- 📖 [Documentation Laravel Sanctum](https://laravel.com/docs/sanctum)
- 🔒 [OWASP XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- 🌐 [MDN CORS Documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)

---

**✅ Migration complétée avec succès !** Votre API est maintenant sécurisée contre les attaques XSS. 🎉
