# Store Order & Inventory Mini-System

A Laravel 12 REST API application for managing products, customers, customer orders and inventory.

## 1. Features

- **Interactive Retail Counter UI**: Single-page POS billing screen (`/`) matching the wireframe brief with customer auto-fill, dynamic product rows, live tax calculations, cash tender change & denomination breakdown, low stock alert widget, and printable receipt modal.
- Product and customer management.
- Create customer orders with multiple products.
- Automatic subtotal, tax and grand total calculation.
- Stock availability validation.
- Automatic stock deduction after successful order creation.
- Customer order history using email.
- Configurable low-stock product API.
- Queued order confirmation email simulation.
- Feature tests for order creation and insufficient stock.
- Database transaction and row locking (`lockForUpdate`) to prevent overselling.

## 2. Technologies Used

- Laravel 12
- PHP
- MySQL
- Eloquent ORM
- REST API
- Laravel Queue
- PHPUnit

## 3. Installation

Clone the repository:

```bash
git clone https://github.com/GowthamMCA24/store-order-inventory-system.git
```

Move into the project directory:

```bash
cd order-inventory-system
```

Install dependencies:

```bash
composer install
```

Create environment file:

```bash
copy .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure your MySQL database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=store_order_inventory
DB_USERNAME=root
DB_PASSWORD=
```

Configure the queue:

```env
QUEUE_CONNECTION=database
```

Run migrations and seed sample data:

```bash
php artisan migrate --seed
```

## 4. Run the Application

Start Laravel:

```bash
php artisan serve
```

Start the queue worker in another terminal:

```bash
php artisan queue:work
```

## 5. Endpoints & UI

| Method | Endpoint | Description |
|---|---|---|
| GET | `/` | Single-page Retail Counter Billing UI (POS) |
| POST | `/api/orders` | Create a customer order |
| GET | `/api/customer/orders?email=arun@example.com` | Get customer order history |
| GET | `/api/products/low-stock` | Get low-stock products |
| GET | `/api/products/low-stock?threshold=30` | Get products below custom threshold |
| GET | `/api/products` | Get list of all available products |
| GET | `/api/customers/search?q=ar` | Search customer by name or email (2+ chars) |

### Create Order Example

```json
{
    "customer_name": "Arun Kumar",
    "customer_email": "arun@example.com",
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ]
}
```

## 6. Postman Collection

A complete, pre-configured Postman Collection is included directly in the repository root:

- **Collection File:** [`postman_collection.json`](postman_collection.json)

### How to Import and Use:
1. Open **Postman**.
2. Click **Import** (top-left corner).
3. Drag and drop `postman_collection.json` from the root of this project (or click **Files** to browse and select it).
4. Click **Import**.
5. The collection **`Store Order & Inventory System API`** will load with pre-configured requests:
   - **Orders:** `POST /api/orders` (Happy path & Insufficient stock 422 edge case).
   - **Customers:** `GET /api/customer/orders` (Order history by email) and `GET /api/customers/search` (2-letter autocomplete).
   - **Products & Inventory:** `GET /api/products/low-stock` (Default & custom threshold) and `GET /api/products` (Catalog list).
6. The collection variable `{{base_url}}` defaults to `http://127.0.0.1:8000`.

## 7. Queue Processing

When an order is created, Laravel dispatches a queued job to simulate an order confirmation email.

The job writes customer and order information to:

`storage/logs/laravel.log`

No SMTP configuration is required.

## 8. Testing

Run the feature tests:

```bash
php artisan test
```

The tests cover:

- Successful order creation and stock deduction.
- Order rejection when product stock is insufficient.

## 9. Design Decisions

- Products and customers use soft deletes.
- Order items preserve the product price and tax percentage at the time of purchase.
- Order creation uses database transactions.
- Product rows are locked using `lockForUpdate()` during stock validation and deduction.
- The order confirmation job runs after the database transaction commits.
- Sample products and customers are inserted using Laravel seeders.

# AI Prompt Log

## AI Tool Used
Google Antigravity IDE

## Development Approach

I used Antigravity IDE to assist with requirement analysis, implementation planning, development, and testing.

### 1. Requirement Analysis
I started by providing the assignment requirements and asking AI to analyze what features and functionalities needed to be implemented.

### 2. Implementation Plan
Based on the requirements, I asked AI to prepare a step-by-step implementation plan covering database design, models, relationships, APIs, business logic, inventory management, queued jobs, and testing.

### 3. Plan Review and Modification
I reviewed the proposed implementation plan before starting development. Whenever I identified changes or improvements, I updated the plan accordingly.

### 4. Implementation
I followed the reviewed implementation plan and developed the application step by step.

### 5. Testing and Validation
I tested the implemented functionality and used AI assistance to understand issues and improve the implementation.

## Prompt History

The actual prompts and AI conversations were maintained as text conversations in Antigravity IDE. This document summarizes the AI-assisted development workflow.