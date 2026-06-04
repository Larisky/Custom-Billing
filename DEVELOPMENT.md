# Development Guide

## Project Structure Overview

```
backend/               # Laravel API
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── PaymentController.php    # Payment endpoints
│   │   │   ├── UserController.php       # User endpoints
│   │   │   └── Controller.php           # Base controller
│   │   └── Middleware/                  # Request middleware
│   ├── Models/
│   │   ├── User.php                     # User model
│   │   └── Payment.php                  # Payment model
│   ├── Events/
│   │   └── PaymentStatusChanged.php     # Broadcast event
│   ├── Services/
│   │   └── PaymentService.php           # Business logic
│   └── Listeners/                       # Event listeners
├── config/                              # Configuration files
├── routes/
│   ├── api.php                          # API routes
│   ├── channels.php                     # WebSocket channels
│   ├── web.php                          # Web routes
│   └── console.php                      # CLI commands
├── database/
│   ├── migrations/                      # Database schemas
│   ├── factories/                       # Test data factories
│   └── seeders/                         # Database seeders
├── tests/
│   ├── Unit/                            # Unit tests
│   ├── Feature/                         # Integration tests
│   └── TestCase.php                     # Test base class
└── bootstrap/                           # Bootstrap files

frontend/              # Vue.js SPA
├── src/
│   ├── components/
│   │   ├── BalanceCard.vue             # Balance display card
│   │   └── PaymentFlowCard.vue         # Payment initiation card
│   ├── services/
│   │   ├── api.js                      # API client
│   │   └── websocket.js                # WebSocket client
│   ├── App.vue                         # Root component
│   └── main.js                         # Entry point
├── public/                              # Static assets
├── index.html                           # HTML template
├── vite.config.js                       # Vite configuration
├── tailwind.config.js                   # Tailwind configuration
└── package.json                         # Dependencies
```

## Code Conventions

### Backend (Laravel/PHP)

1. **Naming Conventions**:
   - Models: Singular, CamelCase (e.g., `User`, `Payment`)
   - Controllers: Singular + Controller (e.g., `PaymentController`)
   - Methods: camelCase (e.g., `processPayment`, `getBalance`)
   - Constants: UPPER_SNAKE_CASE (e.g., `STATUS_PENDING`)

2. **Code Style**:
   - PSR-12 coding standard
   - Use type hints for all function parameters
   - Use return type declarations
   - Maximum line length: 120 characters

3. **Example**:
```php
<?php

namespace App\Services;

class PaymentService
{
    const STATUS_PENDING = 'pending';

    public function processPayment(Payment $payment): Payment
    {
        // Implementation
        return $payment;
    }
}
```

### Frontend (Vue.js/JavaScript)

1. **Naming Conventions**:
   - Components: PascalCase.vue (e.g., `BalanceCard.vue`)
   - Functions: camelCase (e.g., `formatCurrency`)
   - Constants: UPPER_SNAKE_CASE (e.g., `API_URL`)
   - Variables: camelCase (e.g., `paymentData`)

2. **Code Style**:
   - Vue 3 Composition API
   - Script setup syntax
   - Scoped styles by default
   - Two-space indentation

3. **Example**:
```vue
<template>
  <div>{{ balance }}</div>
</template>

<script setup>
import { ref } from 'vue'
import { apiService } from '../services/api'

const balance = ref(0)

const refreshBalance = async () => {
  balance.value = await apiService.getBalance()
}
</script>
```

## Adding New Features

### Add a New API Endpoint

1. **Create Migration** (if needed):
```bash
docker-compose exec app php artisan make:migration add_field_to_table
```

2. **Create Model** (if needed):
```bash
docker-compose exec app php artisan make:model MyModel -m
```

3. **Create Controller**:
```bash
docker-compose exec app php artisan make:controller MyController
```

4. **Define Routes** in `backend/routes/api.php`:
```php
Route::prefix('/items')->group(function () {
    Route::get('/', [ItemController::class, 'index']);
    Route::post('/', [ItemController::class, 'store']);
    Route::get('/{item}', [ItemController::class, 'show']);
});
```

5. **Implement Controller Methods**:
```php
public function index(): JsonResponse
{
    return response()->json([
        'items' => Item::all(),
    ]);
}
```

6. **Add Tests**:
```bash
docker-compose exec app php artisan make:test ItemTest --feature
```

### Add a New Vue Component

1. **Create Component** in `frontend/src/components/`:
```bash
touch frontend/src/components/MyComponent.vue
```

2. **Implement Component**:
```vue
<template>
  <div>{{ title }}</div>
</template>

<script setup>
import { ref } from 'vue'

const title = ref('My Component')
</script>

<style scoped>
/* Component styles */
</style>
```

3. **Import in Parent Component**:
```vue
<script setup>
import MyComponent from './components/MyComponent.vue'
</script>

<template>
  <MyComponent />
</template>
```

### Add a New WebSocket Event

1. **Create Event Class**:
```bash
docker-compose exec app php artisan make:event MyEvent
```

2. **Implement Event** (implement `ShouldBroadcast`):
```php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MyEvent implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [new PrivateChannel('my-channel')];
    }

    public function broadcastAs(): string
    {
        return 'my.event';
    }
}
```

3. **Dispatch Event**:
```php
MyEvent::dispatch($data);
```

4. **Subscribe in Vue**:
```vue
<script setup>
import WebSocketService from '../services/websocket'

const ws = new WebSocketService(wsUrl)
await ws.connect()
ws.subscribe('my-channel', 'my.event', (data) => {
  console.log('Event received:', data)
})
</script>
```

## Database Management

### Run Migrations

```bash
# Run pending migrations
docker-compose exec app php artisan migrate

# Rollback last batch
docker-compose exec app php artisan migrate:rollback

# Refresh all (caution: deletes data)
docker-compose exec app php artisan migrate:refresh

# Refresh with seed
docker-compose exec app php artisan migrate:refresh --seed
```

### Create New Migration

```bash
docker-compose exec app php artisan make:migration create_items_table

# With model creation
docker-compose exec app php artisan make:model Item -m
```

## Testing

### Unit Tests

```bash
# Run specific test
docker-compose exec app php artisan test tests/Unit/PaymentEventTest.php

# Run with coverage
docker-compose exec app php artisan test --coverage

# View coverage report
open coverage/index.html
```

### Feature Tests

```bash
# Run API tests
docker-compose exec app php artisan test tests/Feature/PaymentApiTest.php

# Run with specific method
docker-compose exec app php artisan test --filter=testCanInitiatePayment
```

## Debugging

### Laravel Debugging

```bash
# SSH into container
docker-compose exec app bash

# Use Tinker REPL
php artisan tinker

# Query database
>>> User::all()
>>> User::find(1)->payments
>>> DB::table('payments')->where('status', 'success')->get()

# Check logs
tail -f storage/logs/laravel.log
```

### Frontend Debugging

```bash
# Check browser console
F12 → Console

# Check network requests
F12 → Network

# Vue DevTools
# Install Vue DevTools extension

# Debug WebSocket
ws.onmessage = (event) => {
  console.log('WebSocket:', event.data)
}
```

## Git Workflow

1. **Create Feature Branch**:
```bash
git checkout -b feature/payment-notifications
```

2. **Make Changes**:
```bash
git add .
git commit -m "Add payment notifications feature"
```

3. **Push to Remote**:
```bash
git push origin feature/payment-notifications
```

4. **Create Pull Request**:
- Go to GitHub/GitLab
- Create PR with detailed description
- Request code review

5. **Merge to Main**:
```bash
git checkout main
git merge feature/payment-notifications
git push origin main
```

## Performance Tips

### Backend

1. Use eager loading to prevent N+1 queries:
```php
$users = User::with('payments')->get();
```

2. Cache frequently accessed data:
```php
$data = Cache::remember('key', 3600, function () {
    return ExpensiveQuery::get();
});
```

3. Use queue for long-running operations:
```php
dispatch(new ProcessPayment($payment));
```

### Frontend

1. Use lazy loading for components:
```vue
<script setup>
import { defineAsyncComponent } from 'vue'
const HeavyComponent = defineAsyncComponent(
  () => import('./HeavyComponent.vue')
)
</script>
```

2. Optimize images:
```vue
<img src="image.webp" srcset="image-small.webp 480w" />
```

3. Debounce search input:
```javascript
const search = ref('')
const debouncedSearch = useDebounceFn((term) => {
  // Perform search
}, 500)
```

## Useful Commands

### Docker

```bash
# View all containers
docker-compose ps

# View logs
docker-compose logs -f [service]

# Execute command
docker-compose exec [service] [command]

# Rebuild container
docker-compose build [service]

# Stop services
docker-compose down
```

### Laravel

```bash
# Create model
php artisan make:model ModelName -m

# Create controller
php artisan make:controller ControllerName

# Create migration
php artisan make:migration migration_name

# Run seeds
php artisan db:seed

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Node.js

```bash
# Install dependencies
npm install

# Add package
npm install package-name

# Run dev server
npm run dev

# Build for production
npm run build
```

## Common Issues & Solutions

### Laravel Won't Start

```bash
# Check logs
docker-compose logs app

# Verify database connection
docker-compose exec app php artisan migrate

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Vue Component Not Updating

```javascript
// Use reactive() for complex objects
const state = reactive({ count: 0 })

// Use ref() for primitives
const count = ref(0)

// Force update if necessary
object.value = { ...object.value }
```

### WebSocket Connection Fails

```javascript
// Check WebSocket URL
console.log(import.meta.env.VITE_WS_URL)

// Enable debug logs
ws.onopen = () => console.log('WebSocket connected')
ws.onerror = (e) => console.error('WebSocket error:', e)
```

## Useful Resources

- [Laravel Docs](https://laravel.com/docs)
- [Vue.js Docs](https://vuejs.org)
- [Tailwind CSS Docs](https://tailwindcss.com)
- [Docker Docs](https://docs.docker.com)
- [WebSocket API](https://developer.mozilla.org/en-US/docs/Web/API/WebSocket)
