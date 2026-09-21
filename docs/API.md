# API REST — be-market-back

Base URL : `/api`  
Authentification : Laravel Sanctum avec cookies de session. Le frontend appelle `/sanctum/csrf-cookie`, puis envoie les cookies avec `credentials: 'include'` et le header `X-XSRF-TOKEN`. Aucun token JWT n'est émis.

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
| POST | `/auth/logout` | `auth:sanctum` | Déconnecte la session courante |
| GET | `/auth/user` | `auth:sanctum` | Utilisateur connecté |

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

**Réponse `200` :** même format que register, sans token.

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
  "store_name": "BE-Market",
  "store_email": "contact@be-market.tg",
  "store_phone": ""
}
```

Des valeurs par défaut sont renvoyées si aucun enregistrement n'existe en base.

---

## Panier

Toutes les routes panier requièrent `auth:sanctum`.

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

## Paiement (`/api/payment/*`)

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| POST | `/payment/initiate` | `auth:sanctum` | Initier un paiement |
| GET | `/payment/verify/{id}` | `auth:sanctum` | Vérifier le statut d'un paiement |
| POST | `/payment/callback` | Public (X-Callback-Secret) | Webhook du provider |
| GET | `/payment/history` | `auth:sanctum` | Historique des paiements |

### POST `/payment/initiate`

**Body :**
```json
{
  "order_id": "uuid",
  "payment_method": "tmoney",
  "phone_number": "+228 90 00 00 00"
}
```

**Réponse `201` (sandbox) :**
```json
{
  "id": "uuid",
  "order_id": "uuid",
  "payment_method": "tmoney",
  "amount": 5000,
  "status": "paid",
  "reference": "PAY-XXXX",
  "phone_number": "+228 90 00 00 00",
  "paid_at": "2026-09-20T10:00:00.000000Z",
  "order": { ... }
}
```

**Réponse `201` (production - LigdiCash) :**
```json
{
  "id": "uuid",
  "order_id": "uuid",
  "payment_method": "tmoney",
  "amount": 5000,
  "status": "pending",
  "reference": "ORDER-uuid-XXXX",
  "phone_number": "+228 90 00 00 00",
  "paid_at": null,
  "redirect_url": "https://app.ligdicash.com/pay/v01/redirect/checkout-invoice/...",
  "order": { ... }
}
```

> En production, si `status` est `pending`, le client doit être redirigé vers `redirect_url` pour compléter le paiement.

### GET `/payment/verify/{id}`

Vérifie le statut du paiement auprès du provider en production, ou retourne directement le paiement en sandbox.

**Réponse `200` :**
```json
{
  "id": "uuid",
  "order_id": "uuid",
  "payment_method": "tmoney",
  "amount": 5000,
  "status": "paid",
  "reference": "ORDER-uuid-XXXX",
  "paid_at": "2026-09-20T10:05:00.000000Z",
  "order": { ... }
}
```

### POST `/payment/callback`

Webhook public du provider de paiement. Sécurisé par le header `X-Callback-Secret`.

En production, le provider appelle cette URL pour notifier le changement de statut d'un paiement.

**Header requis :** `X-Callback-Secret: <callback_secret>`

**Body (LigdiCash) :**
```json
{
  "external_id": "ORDER-uuid-XXXX",
  "status": "paid"
}
```

**Réponse `200` :**
```json
{
  "id": "uuid",
  "order_id": "uuid",
  "status": "paid",
  "paid_at": "2026-09-20T10:05:00.000000Z"
}
```

### GET `/payment/history`

**Réponse `200` :** `[Payment[]]` — Liste des paiements de l'utilisateur connecté, triés par date décroissante, avec la commande associée.

---

## Admin (`/api/admin/*`)

Toutes les routes admin requièrent `auth:sanctum` **et** `admin` (`role` `admin` ou `staff`).

| Ressource | Routes |
|-----------|--------|
| Produits | `GET/POST /admin/products`, `GET/PUT/PATCH/DELETE /admin/products/{id}`, `POST /admin/products/upload-image`, `POST /admin/products/{id}/add-stock`, `POST /admin/products/{id}/restore` |
| Catégories | `GET/POST /admin/categories`, `GET/PUT/PATCH/DELETE /admin/categories/{id}`, `POST /admin/categories/{id}/restore` |
| Bannières | `GET/POST /admin/banners`, `GET/PUT/PATCH/DELETE /admin/banners/{banner}` |
| Blog | `GET/POST /admin/blog-posts`, `GET/PUT/PATCH/DELETE /admin/blog-posts/{blog_post}` |
| Coupons | `GET/POST /admin/coupons`, `GET/PUT/PATCH/DELETE /admin/coupons/{coupon}` |
| Flash sales | `GET/POST /admin/flash-sales`, `GET/PUT/PATCH/DELETE /admin/flash-sales/{flash_sale}` |
| FAQ | `GET/POST /admin/faqs`, `GET/PUT/PATCH/DELETE /admin/faqs/{faq}` |
| Pages légales | `GET/POST /admin/legal-pages`, `GET/PUT/PATCH/DELETE /admin/legal-pages/{legal_page}` |
| Statistiques | `GET /admin/statistics`, `GET /admin/statistics/category-sales`, `GET /admin/statistics/coupons`, `GET /admin/statistics/failed-payments`, `GET /admin/statistics/low-stock`, `GET /admin/statistics/new-customers`, `GET /admin/statistics/top-customers`, `GET /admin/statistics/top-products`, `GET /admin/statistics/unsold-products`, `GET/POST /admin/export` |
| Commandes | `GET /admin/orders`, `PUT /admin/orders/{id}`, `POST /admin/orders/{id}/restore` |
| Clients | `GET /admin/customers`, `GET /admin/customers/{id}`, `GET /admin/customers/{id}/orders`, `GET /admin/customers/{id}/loyalty`, `GET /admin/customers/{id}/payments` |
| Paiements | `GET /admin/payments` |
| Fidélité | `GET /admin/loyalty/points`, `GET /admin/loyalty/transactions` |
| Support | `GET/POST /admin/support/tickets`, `GET/PUT /admin/support/tickets/{id}`, `POST /admin/support/tickets/{id}/messages` |
| Témoignages | `GET/POST /admin/testimonials`, `GET/PUT/PATCH/DELETE /admin/testimonials/{testimonial}` |
| Utilisateurs | `GET/POST /admin/users`, `GET/PUT/PATCH/DELETE /admin/users/{id}`, `POST /admin/users/{id}/restore` |
| Upload image | `POST /admin/upload-image` |
| Settings | `GET/PUT /admin/settings` |

### PUT `/admin/settings`

**Body (partiel) :**
```json
{
  "store_name": "BE-Market",
  "store_email": "contact@be-market.tg",
  "store_phone": "+228 90 00 00 00",
  "currency": "FCFA",
  "tax_rate": 0.1,
  "shipping_fee": 2500,
  "free_shipping_threshold": 50000
}
```

### GET `/admin/statistics`

**Réponse `200` :** Statistiques globales + KPIs de période (voir `AdminStatisticsController@index`)

```json
{
  "total_revenue": 125000,
  "total_orders": 45,
  "total_products": 32,
  "total_customers": 16,
  "period_revenue": 25000,
  "period_orders": 10,
  "new_customers": 3,
  "avg_order": 2500,
  "revenue_change": 15.5,
  "orders_change": -2.0,
  "daily_revenue": [
    { "date": "21 Sep", "revenue": 5000, "orders": 2 }
  ],
  "status_data": [
    { "name": "Processing", "value": 3 }
  ],
  "payment_data": [
    { "name": "Tmoney", "value": 5 }
  ]
}
```

### GET `/admin/statistics/{sub-route}`

Sous-routes de statistiques (toutes acceptent `?days=N` pour la période) :

| Sous-route | Description |
|------------|-------------|
| `/admin/statistics/category-sales` | Ventes par catégorie (`orders_count`, `total_quantity`, `total_revenue`) |
| `/admin/statistics/coupons` | Utilisation des coupons (`code`, `usage_count`) |
| `/admin/statistics/failed-payments` | Paiements échoués (`failed`, `cancelled`, `refunded`) |
| `/admin/statistics/low-stock` | Produits en stock faible (seuil configurable via `?threshold=N`) |
| `/admin/statistics/new-customers` | Nouveaux clients récents (limite via `?limit=N`) |
| `/admin/statistics/top-customers` | Clients les plus dépensiers (`total_spent`, `orders_count`) |
| `/admin/statistics/top-products` | Produits les plus vendus (`total_quantity`, `total_revenue`) |
| `/admin/statistics/unsold-products` | Produits jamais vendus |

### GET `/admin/export`

**Query params :** `?type=orders|products|coupons` (défaut: `orders`)

**Réponse :** Fichier CSV téléchargeable.

### GET `/admin/payments`

**Query params :** `?status=`, `?payment_method=`, `?user_id=`

**Réponse `200` :** `[Payment[]]` paginé avec `order` et `user` inclus.

### GET `/admin/loyalty/points`

**Réponse `200` :** `[LoyaltyPoints[]]` paginé (trie par points décroissant, avec `user` inclus).

### GET `/admin/loyalty/transactions`

**Query params :** `?user_id=`, `?type=` (earned/redeemed)

**Réponse `200` :** `[LoyaltyTransaction[]]` paginé avec `user` et `order` inclus.

### GET/POST `/admin/support/tickets`

**Query params (GET) :** `?status=`, `?priority=`, `?search=`

**Réponse (GET) `200` :** `[SupportTicket[]]` paginé avec `user` et `messages.user.profile`.

### GET/PUT/DELETE `/admin/support/tickets/{id}`

**PUT body :** `{ "status"?: "open|in_progress|resolved|closed", "priority"?: "low|normal|high|urgent" }`

### POST `/admin/support/tickets/{id}/messages`

**Body :** `{ "message": "..." }`

Si le ticket est `open`, passe automatiquement à `in_progress`.

### GET/POST `/admin/faqs`

**POST body :** `{ "question", "answer", "category", "sort_order"? }`

### GET/PUT/PATCH/DELETE `/admin/faqs/{faq}`

---

## Autres endpoints (déjà implémentés)

| Domaine | Routes publiques | Routes authentifiées |
|---------|------------------|----------------------|
| Profil | — | `GET/PUT /profile`, `POST /profile/avatar`, `POST /profile/password` |
| Adresses | — | `GET/POST /addresses`, `PUT/DELETE /addresses/{id}` |
| Commandes | — | `POST /checkout`, `GET /orders`, `GET /orders/{id}`, `POST /orders/{id}/cancel`, `GET/POST /admin/orders`, `PUT /admin/orders/{id}`, `POST /admin/orders/{id}/restore` |
| Wishlist | — | `GET/POST /wishlist`, `DELETE /wishlist/{id}` |
| Support | — | `GET/POST /support/tickets`, `GET /support/tickets/{id}`, `POST /support/tickets/{id}/messages` (user) | `GET/POST /admin/support/tickets`, `PUT /admin/support/tickets/{id}`, `POST /admin/support/tickets/{id}/messages` (admin) |
| Contenu | `/banners`, `/blog`, `/blog/{slug}`, `/faqs`, `/legal/{slug}`, `/flash-sales`, `/admin/banners`, `/admin/blog-posts`, `/admin/faqs`, `/admin/legal-pages`, `/admin/flash-sales` | — |
| Newsletter | `POST /newsletter` | — |
| Stock alerts | `POST /stock-notifications` | — |
| Images | `GET /images/{folder}/{path}` | `POST /admin/upload-image` |

---

## Configuration Sanctum

Le frontend doit d'abord obtenir le cookie CSRF :

```http
GET /sanctum/csrf-cookie
```

Puis envoyer les requêtes JSON avec `credentials: 'include'` et le header `X-XSRF-TOKEN`. Les routes protégées utilisent `auth:sanctum` et la session web ; aucun bearer token n'est requis.

En production, `SANCTUM_STATEFUL_DOMAINS` doit contenir le domaine du frontend et `SESSION_SECURE_COOKIE=true` doit être activé. Si le frontend et l'API partagent un domaine parent, configurez également `SESSION_DOMAIN` (par exemple `.tondomaine.com`).
