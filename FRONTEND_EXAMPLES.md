# 🎨 Exemples de Code Frontend - Authentification

### Configuration de Base (`src/api/axios.js`)

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api/v1',

  // ⚠️ CRITIQUE : Permet l'envoi et la réception des cookies HTTP-only
  withCredentials: true,

  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },

  // Timeout après 10 secondes
  timeout: 10000
});

export default api;
```

---

## 2. React + Axios

### Service d'Authentification (`src/services/authService.js`)

```javascript
import api from '../api/axios';

const authService = {
  /**
   * Connexion utilisateur
   * Le refresh_token est automatiquement stocké dans un cookie HTTP-only
   */
  async login(email, password) {
    try {
      const response = await api.post('/auth/login', {
        email,
        password
      });

      const { access_token, user, expires_in } = response.data.data;

      // Stocker uniquement l'access_token (courte durée)
      localStorage.setItem('access_token', access_token);
      localStorage.setItem('user', JSON.stringify(user));

      // Calculer l'expiration
      const expiresAt = Date.now() + (expires_in * 1000);
      localStorage.setItem('token_expires_at', expiresAt);

      return { user, access_token };
    } catch (error) {
      throw this.handleError(error);
    }
  },

  /**
   * Rafraîchir l'access token
   * Le refresh_token est lu automatiquement depuis le cookie
   */
  async refreshToken() {
    try {
      const response = await api.post('/auth/refresh-token');

      const { access_token, expires_in } = response.data.data;

      // Mettre à jour l'access_token
      localStorage.setItem('access_token', access_token);

      const expiresAt = Date.now() + (expires_in * 1000);
      localStorage.setItem('token_expires_at', expiresAt);

      return access_token;
    } catch (error) {
      // Si le refresh échoue, déconnecter l'utilisateur
      this.logout();
      throw this.handleError(error);
    }
  },

  /**
   * Déconnexion
   * Invalide le cookie HTTP-only côté serveur
   */
  async logout() {
    try {
      const token = localStorage.getItem('access_token');

      if (token) {
        await api.post('/auth/logout', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
      }
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error);
    } finally {
      // Nettoyer le localStorage dans tous les cas
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
      localStorage.removeItem('token_expires_at');
    }
  },

  /**
   * Vérifier si l'utilisateur est connecté
   */
  isAuthenticated() {
    const token = localStorage.getItem('access_token');
    const expiresAt = localStorage.getItem('token_expires_at');

    if (!token || !expiresAt) return false;

    // Vérifier si le token n'est pas expiré
    return Date.now() < parseInt(expiresAt);
  },

  /**
   * Obtenir l'access token
   */
  getAccessToken() {
    return localStorage.getItem('access_token');
  },

  /**
   * Gestion centralisée des erreurs
   */
  handleError(error) {
    if (error.response) {
      // Erreur retournée par le serveur
      const { status, data } = error.response;

      switch (status) {
        case 401:
          return new Error(data.message || 'Non authentifié');
        case 422:
          return new Error(data.message || 'Données invalides');
        case 429:
          return new Error('Trop de tentatives. Veuillez patienter.');
        default:
          return new Error(data.message || 'Une erreur est survenue');
      }
    } else if (error.request) {
      return new Error('Serveur injoignable. Vérifiez votre connexion.');
    } else {
      return new Error(error.message);
    }
  }
};

export default authService;
```

### Composant de Login React (`src/components/Login.jsx`)

```jsx
import React, { useState } from 'react';
import authService from '../services/authService';
import { useNavigate } from 'react-router-dom';

function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const { user } = await authService.login(email, password);
      console.log('Connecté:', user);
      navigate('/dashboard');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-form">
      <h2>Connexion</h2>

      {error && <div className="error">{error}</div>}

      <form onSubmit={handleSubmit}>
        <input
          type="email"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />

        <input
          type="password"
          placeholder="Mot de passe"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />

        <button type="submit" disabled={loading}>
          {loading ? 'Connexion...' : 'Se connecter'}
        </button>
      </form>
    </div>
  );
}

export default Login;
```

### Intercepteur Axios avec Auto-Refresh (`src/api/axios.js`)

```javascript
import axios from 'axios';
import authService from '../services/authService';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api/v1',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Intercepteur de requête : Ajouter le token automatiquement
api.interceptors.request.use(
  (config) => {
    const token = authService.getAccessToken();

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur de réponse : Auto-refresh si 401
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });

  failedQueue = [];
};

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // Si 401 et pas déjà tenté de refresh
    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        // Mettre en file d'attente pendant le refresh
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then(token => {
            originalRequest.headers.Authorization = `Bearer ${token}`;
            return api(originalRequest);
          })
          .catch(err => Promise.reject(err));
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const newToken = await authService.refreshToken();
        processQueue(null, newToken);

        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return api(originalRequest);
      } catch (refreshError) {
        processQueue(refreshError, null);
        authService.logout();
        window.location.href = '/login';
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(error);
  }
);

export default api;
```

---

## 3. Vue.js + Axios

### Store Vuex/Pinia (`src/store/auth.js`)

```javascript
import { defineStore } from 'pinia';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    accessToken: null,
    isAuthenticated: false
  }),

  actions: {
    async login(email, password) {
      try {
        const response = await api.post('/auth/login', {
          email,
          password
        });

        const { access_token, user } = response.data.data;

        this.accessToken = access_token;
        this.user = user;
        this.isAuthenticated = true;

        // Stocker dans localStorage
        localStorage.setItem('access_token', access_token);
        localStorage.setItem('user', JSON.stringify(user));

        return { success: true, user };
      } catch (error) {
        return {
          success: false,
          message: error.response?.data?.message || 'Erreur de connexion'
        };
      }
    },

    async refreshToken() {
      try {
        const response = await api.post('/auth/refresh-token');
        const { access_token } = response.data.data;

        this.accessToken = access_token;
        localStorage.setItem('access_token', access_token);

        return access_token;
      } catch (error) {
        this.logout();
        throw error;
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout');
      } catch (error) {
        console.error('Erreur logout:', error);
      } finally {
        this.accessToken = null;
        this.user = null;
        this.isAuthenticated = false;
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
      }
    },

    initializeAuth() {
      const token = localStorage.getItem('access_token');
      const user = localStorage.getItem('user');

      if (token && user) {
        this.accessToken = token;
        this.user = JSON.parse(user);
        this.isAuthenticated = true;
      }
    }
  }
});
```

### Composant Login Vue 3 (`src/views/Login.vue`)

```vue
<template>
  <div class="login-container">
    <h2>Connexion</h2>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <form @submit.prevent="handleLogin">
      <input
        v-model="email"
        type="email"
        placeholder="Email"
        required
      />

      <input
        v-model="password"
        type="password"
        placeholder="Mot de passe"
        required
      />

      <button type="submit" :disabled="loading">
        {{ loading ? 'Connexion...' : 'Se connecter' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
  error.value = '';
  loading.value = true;

  try {
    const result = await authStore.login(email.value, password.value);

    if (result.success) {
      router.push('/dashboard');
    } else {
      error.value = result.message;
    }
  } catch (err) {
    error.value = 'Une erreur est survenue';
  } finally {
    loading.value = false;
  }
};
</script>
```

---

## 4. Vanilla JavaScript (Fetch API)

### Service d'Authentification (`auth.js`)

```javascript
const API_BASE_URL = 'http://localhost:8000/api/v1';

const authService = {
  async login(email, password) {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        credentials: 'include', // ⚠️ IMPORTANT
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Erreur de connexion');
      }

      const { access_token, user } = data.data;

      // Stocker l'access token
      localStorage.setItem('access_token', access_token);
      localStorage.setItem('user', JSON.stringify(user));

      return { user, access_token };
    } catch (error) {
      throw error;
    }
  },

  async refreshToken() {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/refresh-token`, {
        method: 'POST',
        credentials: 'include', // ⚠️ IMPORTANT
        headers: {
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error('Refresh token invalide');
      }

      const { access_token } = data.data;
      localStorage.setItem('access_token', access_token);

      return access_token;
    } catch (error) {
      this.logout();
      throw error;
    }
  },

  async logout() {
    const token = localStorage.getItem('access_token');

    try {
      await fetch(`${API_BASE_URL}/auth/logout`, {
        method: 'POST',
        credentials: 'include', // ⚠️ IMPORTANT
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      });
    } catch (error) {
      console.error('Erreur logout:', error);
    } finally {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
    }
  },

  getAccessToken() {
    return localStorage.getItem('access_token');
  }
};

// Exemple d'utilisation
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;

  try {
    const { user } = await authService.login(email, password);
    console.log('Connecté:', user);
    window.location.href = '/dashboard.html';
  } catch (error) {
    document.getElementById('error').textContent = error.message;
  }
});
```

---

## 5. Gestion des Erreurs

### Helper de Gestion d'Erreurs

```javascript
export const handleApiError = (error) => {
  if (error.response) {
    // Erreur retournée par le serveur
    const { status, data } = error.response;

    switch (status) {
      case 401:
        return {
          title: 'Non authentifié',
          message: data.message || 'Veuillez vous connecter',
          shouldLogout: true
        };

      case 403:
        return {
          title: 'Accès refusé',
          message: 'Vous n\'avez pas les permissions nécessaires'
        };

      case 422:
        return {
          title: 'Validation échouée',
          message: data.message || 'Données invalides',
          errors: data.errors
        };

      case 429:
        return {
          title: 'Trop de requêtes',
          message: `Veuillez patienter ${data.retry_after || 60} secondes`
        };

      case 500:
        return {
          title: 'Erreur serveur',
          message: 'Une erreur est survenue. Veuillez réessayer.'
        };

      default:
        return {
          title: 'Erreur',
          message: data.message || 'Une erreur est survenue'
        };
    }
  } else if (error.request) {
    return {
      title: 'Erreur réseau',
      message: 'Impossible de joindre le serveur. Vérifiez votre connexion.'
    };
  } else {
    return {
      title: 'Erreur',
      message: error.message || 'Une erreur est survenue'
    };
  }
};
```

---

## 6. Intercepteurs pour Auto-Refresh

### Version Complète avec Queue

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  withCredentials: true
});

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(promise => {
    if (error) {
      promise.reject(error);
    } else {
      promise.resolve(token);
    }
  });
  failedQueue = [];
};

// Request Interceptor
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then((token) => {
            originalRequest.headers.Authorization = `Bearer ${token}`;
            return api(originalRequest);
          })
          .catch((err) => Promise.reject(err));
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const { data } = await api.post('/auth/refresh-token');
        const newToken = data.data.access_token;

        localStorage.setItem('access_token', newToken);
        processQueue(null, newToken);

        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return api(originalRequest);
      } catch (refreshError) {
        processQueue(refreshError, null);
        localStorage.removeItem('access_token');
        window.location.href = '/login';
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(error);
  }
);

export default api;
```

---

## 📝 Checklist Frontend

- [ ] Ajouter `credentials: 'include'` (Fetch) ou `withCredentials: true` (Axios)
- [ ] Supprimer tout code gérant `refresh_token` dans localStorage
- [ ] Implémenter l'intercepteur de requête pour ajouter le Bearer token
- [ ] Implémenter l'intercepteur de réponse pour auto-refresh sur 401
- [ ] Gérer la file d'attente des requêtes pendant le refresh
- [ ] Tester le flux complet : login → requête protégée → refresh → logout

---

**✅ Tous vos besoins frontend sont couverts !** Adaptez ces exemples selon votre framework et architecture.
