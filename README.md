# Store Order & Inventory Mini-System

A Laravel 12 REST API application for managing products, customers, customer orders and inventory.

## 1. Features

- Product and customer management.
- Create customer orders with multiple products.
- Automatic subtotal, tax and grand total calculation.
- Stock availability validation.
- Automatic stock deduction after successful order creation.
- Customer order history using email.
- Configurable low-stock product API.
- Queued order confirmation email simulation.
- Feature tests for order creation and insufficient stock.
- Database transaction and row locking to prevent overselling.

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

## 5. API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/orders` | Create a customer order |
| GET | `/api/customer/orders?email=arun@example.com` | Get customer order history |
| GET | `/api/products/low-stock` | Get low-stock products |
| GET | `/api/products/low-stock?threshold=30` | Get products below custom threshold |

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

## 6. Queue Processing

When an order is created, Laravel dispatches a queued job to simulate an order confirmation email.

The job writes customer and order information to:

`storage/logs/laravel.log`

No SMTP configuration is required.

## 7. Testing

Run the feature tests:

```bash
php artisan test
```

The tests cover:

- Successful order creation and stock deduction.
- Order rejection when product stock is insufficient.

## 8. Design Decisions

- Products and customers use soft deletes.
- Order items preserve the product price and tax percentage at the time of purchase.
- Order creation uses database transactions.
- Product rows are locked using `lockForUpdate()` during stock validation and deduction.
- The order confirmation job runs after the database transaction commits.
- Sample products and customers are inserted using Laravel seeders.