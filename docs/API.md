# API REST — be-market-back

Base URL : `/api`  
Authentification : Bearer JWT (Laravel Passport) via header `Authorization: Bearer <token>`

## Conventions

- Toutes les réponses sont en JSON (`Accept: application/json`).
- Les erreurs de validation renvoient `422` avec `{ "errors": { "champ": ["message"] } }`.
- Les erreurs métier renvoient `{ "message": "..." }`.
- Les identifiants sont des UUID v4.

---

## Auth (`/api/auth/*`)

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| POST | `/auth/register` | Public | Inscription |
| POST | `/auth/login` | Public | Connexion |
| POST | `/auth/logout` | `auth:api` | Révoque le token courant |
| GET | `/auth/user` | `auth:api` | Utilisateur connecté |
| POST | `/auth/refresh` | `auth:api` | Renouvelle le token |

### POST `/auth/register`

**Body :**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "full_name": "Jean Dupont"
}
```

> `full_name` ou `name` est accepté.

**Réponse `201` :**
```json
{
  "token": "eyJ...",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "created_at": "2026-06-26T10:00:00.000000Z",
    "updated_at": "2026-06-26T10:00:00.000000Z"
  },
  "profile": {
    "id": "uuid",
    "user_id": "uuid",
    "email": "user@example.com",
    "full_name": "Jean Dupont",
    "phone": null,
    "avatar_url": null,
    "is_admin": false,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### POST `/auth/login`

**Body :**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse `200` :** même format que register.

### GET `/auth/user`

**Réponse `200` :**
```json
{
  "user": { "id", "email", "created_at", "updated_at" },
  "profile": { "id", "user_id", "email", "full_name", "phone", "avatar_url", "is_admin", "created_at", "updated_at" } | null
}
```

---

## Catégories

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| GET | `/categories` | Public | Liste des catégories actives |

**Réponse `200` :** `Category[]`
```json
[
  {
    "id": "uuid",
    "name": "Téléphones",
    "slug": "telephones",
    "description": "...",
    "image_url": "https://...",
    "created_at": "..."
  }
]
```

---

## Catalogue produits

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| GET | `/products` | Public | Liste / filtre produits |
| GET | `/products/{slug}` | Public | Détail d'un produit |

### GET `/products` — Query params

| Param | Type | Description |
|-------|------|-------------|
| `category` | string | ID ou slug de catégorie |
| `category_id` | uuid | Filtre par ID catégorie |
| `exclude_id` | uuid | Exclure un produit (produits similaires) |
| `featured` | bool | `true` pour les produits mis en avant |
| `search` | string | Recherche nom / description / SKU |
| `brand` | string | Filtre sur `specs.brand` |
| `min_price` | number | Prix minimum |
| `max_price` | number | Prix maximum |
| `sort` | string | `price_asc`, `price_desc`, `newest`, `name_asc`, `name_desc` |
| `limit` | int | Limite le nombre de résultats |
| `page` | int | Active la pagination (sinon tableau complet) |
| `per_page` | int | Taille de page (défaut 20, avec `page`) |

**Réponse `200` :** `Product[]` (tableau simple, sauf si `page` est fourni)
```json
[
  {
    "id": "uuid",
    "name": "iPhone 15 Pro Max",
    "slug": "iphone-15-pro-max",
    "sku": "APP-I15PM-001",
    "description": "...",
    "price": "1250000.00",
    "compare_price": "1350000.00",
    "stock": 8,
    "category_id": "uuid",
    "images": ["https://..."],
    "specs": { "brand": "Apple", "storage": "256GB" },
    "is_featured": true,
    "is_active": true,
    "weight": "221.00",
    "created_at": "...",
    "updated_at": "...",
    "category": { "id", "name", "slug", "description", "image_url", "created_at" }
  }
]
```

### GET `/products/{slug}`

**Réponse `200` :** un objet `Product`  
**Réponse `404` :** `{ "message": "Product not found." }`

---

## Settings

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| GET | `/settings` | Public | Paramètres boutique |

**Réponse `200` :**
```json
{
  "shipping_fee": 2500,
  "free_shipping_threshold": 50000,
  "tax_rate": 0.1,
  "currency": "FCFA",
  "store_name": "Electronica",
  "store_email": "contact@electronica.tg",
  "store_phone": ""
}
```

Des valeurs par défaut sont renvoyées si aucun enregistrement n'existe en base.

---

## Panier

Toutes les routes panier requièrent `auth:api`.

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/cart` | Liste des articles |
| POST | `/cart/items` | Ajouter un article |
| PUT | `/cart/items/{id}` | Modifier la quantité |
| DELETE | `/cart/items/{id}` | Supprimer un article |
| DELETE | `/cart` | Vider le panier |

### GET `/cart`

**Réponse `200` :** `CartItem[]`
```json
[
  {
    "id": "uuid",
    "cart_id": "uuid",
    "product_id": "uuid",
    "quantity": 2,
    "product": { "...Product" }
  }
]
```

### POST `/cart/items`

**Body :**
```json
{
  "product_id": "uuid",
  "quantity": 1
}
```

**Réponse `201` :** `CartItem` avec `product` inclus.

### PUT `/cart/items/{id}`

**Body :**
```json
{ "quantity": 3 }
```

**Réponse `200` :** `CartItem` avec `product` inclus.

### DELETE `/cart/items/{id}` et DELETE `/cart`

**Réponse `204` :** corps vide.

---

## Admin (`/api/admin/*`)

Toutes les routes admin requièrent `auth:api` **et** `admin` (`profile.is_admin = true` ou `users.is_admin = true`).

| Ressource | Routes |
|-----------|--------|
| Produits | `GET/POST /admin/products`, `GET/PUT/PATCH/DELETE /admin/products/{id}` |
| Catégories | `GET/POST /admin/categories`, `GET/PUT/PATCH/DELETE /admin/categories/{id}` |
| Bannières | `GET/POST /admin/banners`, ... |
| Blog | `GET/POST /admin/blog-posts`, ... |
| Coupons | `GET/POST /admin/coupons`, ... |
| Flash sales | `GET/POST /admin/flash-sales`, ... |
| Settings | `GET/PUT /admin/settings` |
| Commandes | `GET /admin/orders` |
| Clients | `GET /admin/customers` |
| Statistiques | `GET /admin/statistics`, `GET /admin/export` |

### PUT `/admin/settings`

**Body (partiel) :**
```json
{
  "store_name": "Electronica",
  "store_email": "contact@electronica.tg",
  "store_phone": "+228 90 00 00 00",
  "currency": "FCFA",
  "tax_rate": 0.1,
  "shipping_fee": 2500,
  "free_shipping_threshold": 50000
}
```

---

## Autres endpoints (déjà implémentés)

| Domaine | Routes publiques | Routes authentifiées |
|---------|------------------|----------------------|
| Profil | — | `GET/PUT /profile` |
| Adresses | — | `GET/POST /addresses`, `PUT/DELETE /addresses/{id}` |
| Commandes | — | `POST /checkout`, `GET /orders`, `GET /orders/{id}` |
| Wishlist | — | `GET/POST /wishlist`, `DELETE /wishlist/{id}` |
| Support | — | `GET/POST /support/tickets`, `GET /support/tickets/{id}`, `POST /support/tickets/{id}/messages` |
| Contenu | `/banners`, `/blog`, `/blog/{slug}`, `/faqs`, `/legal/{slug}`, `/flash-sales` | — |
| Newsletter | `POST /newsletter` | — |
| Stock alerts | `POST /stock-notifications` | — |

---

## Configuration Passport

```bash
php artisan migrate
php artisan passport:install
```

Le token JWT est obtenu via `/api/auth/login` ou `/api/auth/register` et transmis dans chaque requête protégée :

```
Authorization: Bearer <token>
```
