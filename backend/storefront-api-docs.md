# Storefront Jewellery API Documentation

This document describes the public storefront jewellery API endpoints for categories and filters.

---

## 1. Get Jewellery Categories

Retrieve a list of categories populated from the category master table (`jewelery_type`), along with the count of approved and available products matching each category.

* **Endpoint**: `GET /api/storefront/jewellery/categories`
* **Authentication**: None (Public)
* **Response Header**: `Content-Type: application/json`

### Success Response

* **Status Code**: `200 OK`
* **JSON Body**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "name": "Ring",
        "slug": "ring",
        "image": null,
        "products_count": 10
      },
      {
        "id": 2,
        "name": "Bracelet",
        "slug": "bracelet",
        "image": "https://example.com/images/categories/bracelet.png",
        "products_count": 5
      }
    ]
  }
  ```

---

## 2. Get Jewellery Filters

Retrieve available listing filters dynamically, including distinct types, master categories with their product counts, distinct metals, and the price range.

* **Endpoint**: `GET /api/storefront/jewellery/filters`
* **Authentication**: None (Public)
* **Response Header**: `Content-Type: application/json`

### Success Response

* **Status Code**: `200 OK`
* **JSON Body**:
  ```json
  {
    "success": true,
    "data": {
      "types": [
        "Bracelet",
        "Necklace",
        "Ring"
      ],
      "categories": [
        {
          "id": 1,
          "name": "Ring",
          "slug": "ring",
          "image": null,
          "products_count": 10
        },
        {
          "id": 2,
          "name": "Bracelet",
          "slug": "bracelet",
          "image": "https://example.com/images/categories/bracelet.png",
          "products_count": 5
        }
      ],
      "metals": [
        "Gold",
        "Platinum"
      ],
      "price_range": {
        "min": 1000.00,
        "max": 5000.00
      }
    }
  }
  ```

---

## 3. Product Catalog Listing (Filtering by Category)

You can filter products by category slug using the `category` query parameter. This filters products where the `type` column or virtual `specifications->category` matches the category.

* **Endpoint**: `GET /api/storefront/jewellery`
* **Query Parameters**:
  * `category` (optional): Single slug (e.g. `ring`), comma-separated list of slugs (e.g. `ring,bracelet`), or legacy category name (e.g. `Bridal`).
  * `type` (optional): Keep existing type filters intact.
