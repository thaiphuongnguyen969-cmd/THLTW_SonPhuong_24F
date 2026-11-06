# Postman: Categories, Brands, and Products API

## Base URL and authentication

Set `base_url` to the running Laravel backend, for example `http://127.0.0.1:8000`.

Catalog `GET` endpoints are public. Creating, updating, and deleting catalog data requires both:

1. A valid Sanctum bearer token.
2. An active user whose persisted database role is `admin`.

Send the token in Postman under **Authorization → Bearer Token**. Do not send `role` or `status` as a way to grant access; the server reads those values from the authenticated database user. The current seeded users have the `customer` role, so their tokens receive HTTP 403 for write endpoints. No admin account or token is created by this API.

All request bodies below use `Content-Type: application/json` and `Accept: application/json`.

## Suggested test order

First verify public reads:

1. `GET {{base_url}}/api/categories`
2. `GET {{base_url}}/api/brands`
3. `GET {{base_url}}/api/products`
4. Open a category/brand/product by its slug.
5. Try pagination and the documented filters.

Only after an authorized admin token is provisioned through the project's approved operational process should you test POST, PUT/PATCH, or DELETE.

## Categories

| Method | URL | Access |
|---|---|---|
| GET | `{{base_url}}/api/categories` | Public |
| GET | `{{base_url}}/api/categories/{slug}` | Public for active categories |
| POST | `{{base_url}}/api/categories` | Active admin |
| PUT/PATCH | `{{base_url}}/api/categories/{slug}` | Active admin |
| DELETE | `{{base_url}}/api/categories/{slug}` | Active admin |

### GET list query parameters

- `per_page`: 1–100; default 15.
- `status`: `active` or `inactive`. Inactive categories are visible only to an active admin.
- `parent_id`: filter by parent ID. Use an empty value to request top-level categories.

Example:

```http
GET {{base_url}}/api/categories?per_page=10&parent_id=
```

### Create category

`slug` may be omitted; the API derives it from `name`. If the slug is already used, validation returns HTTP 422.

```json
{
  "name": "Áo sơ mi",
  "parent_id": null,
  "description": "Danh mục áo sơ mi",
  "status": "active",
  "image": "https://example.test/images/shirts.jpg"
}
```

The category cannot be its own parent or a descendant of itself. Deletion returns HTTP 409 while the category has products (including soft-deleted products) or child categories.

## Brands

| Method | URL | Access |
|---|---|---|
| GET | `{{base_url}}/api/brands` | Public |
| GET | `{{base_url}}/api/brands/{slug}` | Public for active brands |
| POST | `{{base_url}}/api/brands` | Active admin |
| PUT/PATCH | `{{base_url}}/api/brands/{slug}` | Active admin |
| DELETE | `{{base_url}}/api/brands/{slug}` | Active admin |

### GET list query parameters

- `per_page`: 1–100; default 15.
- `status`: `active` or `inactive`. Inactive brands are visible only to an active admin.

### Create brand

```json
{
  "name": "Example Apparel",
  "slug": "example-apparel",
  "description": "Mô tả thương hiệu",
  "logo": "https://example.test/brands/example.png",
  "status": "active"
}
```

Both brand name and slug must be unique. `slug` may be omitted and is then derived from the name. Deletion returns HTTP 409 while any product, including a soft-deleted product, references the brand.

## Products

| Method | URL | Access |
|---|---|---|
| GET | `{{base_url}}/api/products` | Public |
| GET | `{{base_url}}/api/products/{slug}` | Public for active products |
| POST | `{{base_url}}/api/products` | Active admin |
| PUT/PATCH | `{{base_url}}/api/products/{slug}` | Active admin |
| DELETE | `{{base_url}}/api/products/{slug}` | Active admin |

### GET list query parameters

- `per_page`: 1–100; default 15.
- `search`: search product name.
- `category_id`, `brand_id`: filter by existing IDs.
- `min_price`, `max_price`: inclusive base-price range; minimum must not exceed maximum.
- `status`: `active` or `inactive`; inactive products are visible only to an active admin.
- `sort_by`: `name`, `price`, or `created_at`.
- `sort_direction`: `asc` or `desc`.

Example:

```http
GET {{base_url}}/api/products?search=áo&category_id=3&min_price=100000&max_price=800000&sort_by=price&sort_direction=asc&per_page=12
```

Product detail includes category, brand, images, active variants with size/color, and currently active promotions. Public responses expose only variant availability, not SKU or exact stock; the legacy product quantity is returned only to an active admin. The list endpoint eagerly loads only category and brand.

### Create product

```json
{
  "category_id": 3,
  "brand_id": 1,
  "name": "Áo thun thử nghiệm",
  "slug": "ao-thun-thu-nghiem",
  "description": "Sản phẩm kiểm thử",
  "price": 250000,
  "sale_price": 220000,
  "status": "active",
  "image": "products/ao-thun-thu-nghiem.jpg",
  "quantity": 0
}
```

`slug` may be omitted on create and is derived from the name. On update, changing a product name does not implicitly change its slug; send a new slug explicitly if needed. `sale_price`, when set, must be lower than `price`.

`image` and `quantity` are legacy product columns. This API does not create product image rows, variants, sizes/colors, or variant stock. Do not use `quantity` as a substitute for variant inventory.

Product deletion is a soft delete. The API does not accept `deleted_at`; it returns success after marking the product deleted.

## Response examples

### Successful list response

```json
{
  "success": true,
  "message": "Lấy danh sách sản phẩm thành công.",
  "data": [
    {
      "id": 12,
      "name": "Áo thun thử nghiệm",
      "slug": "ao-thun-thu-nghiem",
      "price": "250000.00",
      "sale_price": "220000.00",
      "status": "active"
    }
  ],
  "links": {},
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

Laravel supplies pagination links and metadata; the values above are illustrative.

### Validation error (HTTP 422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "slug": [
      "The slug has already been taken."
    ]
  }
}
```

### Authorization and conflicts

- HTTP 401: missing or invalid Sanctum token on a protected write route.
- HTTP 403: authenticated user is not an active admin, or requests an inactive record without admin access.
- HTTP 404: slug does not resolve to an accessible active record.
- HTTP 409: deleting a category/brand that is still referenced.
- HTTP 201: successful create.
- HTTP 200: successful read/update/delete.

## Write request examples (do not send until admin access is ready)

### Update product — PATCH

```http
PATCH {{base_url}}/api/products/ao-thun-thu-nghiem
```

```json
{
  "sale_price": 210000,
  "status": "active"
}
```

### Delete product

```http
DELETE {{base_url}}/api/products/ao-thun-thu-nghiem
```

### Update category — PUT

```http
PUT {{base_url}}/api/categories/ao-so-mi
```

```json
{
  "name": "Áo sơ mi",
  "slug": "ao-so-mi",
  "parent_id": null,
  "description": "Danh mục áo sơ mi",
  "status": "active",
  "image": null
}
```

### Create brand

```http
POST {{base_url}}/api/brands
```

```json
{
  "name": "Example Apparel",
  "slug": "example-apparel",
  "description": "Mô tả thương hiệu",
  "logo": null,
  "status": "active"
}
```
