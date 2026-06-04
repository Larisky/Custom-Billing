# System Architecture

## High-Level Overview

```
User Browser (SPA)
    ↓
Vue.js Frontend
├─ BalanceCard Component
├─ PaymentFlowCard Component
└─ WebSocket Client (Reconnect Logic)
    ↓
┌────────────────────────────────────┐
│                                    │
│  API Gateway (Nginx)               │
│  ├─ Load Balancer                 │
│  ├─ SSL/TLS Termination           │
│  └─ Rate Limiting                 │
│                                    │
└─────────────┬──────────────────────┘
              │
    ┌─────────┴──────────┬──────────────┐
    ↓                    ↓              ↓
┌─────────┐      ┌────────────┐   ┌──────────┐
│ Laravel │      │ Reverb     │   │ Redis    │
│   App   │      │ WebSocket  │   │ Queue    │
│   (PHP) │      │  Server    │   │          │
└────┬────┘      └────┬───────┘   └────┬─────┘
     │                │                 │
     └────────┬───────┴─────────┬───────┘
              │                 │
         ┌────▼─────────────────▼──┐
         │   MySQL Database        │
         │   ├─ Users Table        │
         │   ├─ Payments Table     │
         │   └─ Jobs Table         │
         └────────────────────────┘
```

## Component Interactions

### Payment Processing Flow

```
1. User initiates payment in SPA
   ↓
2. Vue sends POST /api/users/{id}/payments
   ↓
3. Laravel PaymentController.initiate()
   ├─ Validates input
   ├─ Creates Payment record (status: pending)
   └─ Returns payment_id
   ↓
4. User clicks "Process Payment"
   ↓
5. Vue sends POST /api/users/{id}/payments/{paymentId}/process
   ↓
6. Laravel PaymentController.process()
   ├─ Calls PaymentService.processPayment()
   ├─ Updates status to "processing"
   ├─ Simulates gateway call (70% success)
   └─ If success: PaymentService.markPaymentSuccess()
      ├─ Updates status to "success"
      ├─ Increments user balance
      └─ Dispatches PaymentStatusChanged event
   ↓
7. Event broadcast through Reverb
   ├─ Serialized to Redis
   ├─ Delivered to WebSocket connection
   └─ Published on private channel: payment.user.{userId}
   ↓
8. Frontend WebSocket receives event
   ├─ Parses payment data
   ├─ Updates balance (reactive)
   ├─ Shows notification
   └─ UI reflects changes instantly
```

### Real-time Update Flow

```
User 1 Browser          User 1 Payment Event        Reverb Server       User 2 Browser
    │                         │                           │                   │
    ├─ Process Payment ────────┤                           │                   │
    │                          ├─ PaymentStatusChanged ─┬──┤                   │
    │                          │   Dispatch             │  │                   │
    │                          │                        ├──┤ Broadcast to      │
    │                          │                        │  │ Channel:          │
    │                          │                        │  │ payment.user.1    │
    │                          │                        │  │                   │
    │                          │                        └──┤ User 2 NOT        │
    │                          │                           │ subscribed        │
    │ ◄─ Receive Event ────────────────────────────────────┤ (security)       │
    │ Update Balance                                       │                   │
    │ Show Notification                                    │                   │
    │                                                       │                   │
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    balance DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

INDEX: email, created_at
```

### Payments Table
```sql
CREATE TABLE payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    amount DECIMAL(10,2),
    status ENUM('pending', 'processing', 'success', 'failed', 'refunded'),
    payment_method VARCHAR(255),
    reference_id VARCHAR(255) UNIQUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INDEXES: user_id, status, created_at, reference_id
```

## Event Broadcasting Architecture

### Event Class Structure
```
PaymentStatusChanged Event
├─ Properties
│  ├─ $payment (Payment model)
│  ├─ $previousStatus (string)
│  └─ $newStatus (string)
├─ broadcastOn() → Returns private channels
├─ broadcastAs() → "payment.status.changed"
└─ broadcastWith() → Event payload
```

### Broadcasting Pipeline
```
Event Dispatch
    ↓
Event Listener (optional)
    ↓
Queue System (Redis)
    ↓
Event Serialization
    ↓
Reverb Broadcast
    ↓
Redis Pub/Sub
    ↓
WebSocket Delivery
    ↓
Client Reception
```

## Queue Architecture

### Redis Queue Structure
```
Queue (Redis)
└─ default
   ├─ Job 1 (PaymentStatusChanged)
   ├─ Job 2 (EmailNotification)
   └─ Job N (...)

Redis Lists:
- queues:default (pending jobs)
- queues:default:reserved (processing jobs)
- queues:default:failed (failed jobs)
```

### Queue Worker Flow
```
Queue Worker (Daemon)
    ↓
Poll Redis Queue (every 3 seconds)
    ↓
Get Job from Queue
    ↓
Deserialize Job
    ↓
Execute Job Handler
    ↓
If Success: Remove from Queue
If Failure: Retry (max 3 attempts)
    ↓
Mark Failed if All Retries Exhausted
    ↓
Continue Loop
```

## WebSocket Connection Management

### Connection Lifecycle
```
1. User opens SPA
   ↓
2. Frontend creates WebSocket instance
   ↓
3. Establishes connection to Reverb (ws://localhost:8080)
   ↓
4. Connection established
   ├─ readyState = OPEN
   ├─ Listeners registered
   └─ Subscriptions active
   ↓
5. Receive messages
   ├─ Parse JSON
   ├─ Route to callbacks
   └─ Update UI (reactive)
   ↓
6. Connection closes (network error, user navigates)
   ├─ readyState = CLOSED
   ├─ Attempt reconnect
   ├─ Exponential backoff
   └─ Max 5 attempts
   ↓
7. If reconnected
   └─ Re-establish subscriptions
```

### Channel Authorization
```
Private Channel: payment.user.{userId}
    ↓
Reverb receives subscription request
    ↓
Execute authorization callback (routes/channels.php)
    ↓
Check: (int) $user->id === (int) $userId
    ├─ YES: Grant access
    │  └─ Add to channel
    └─ NO: Deny access
       └─ Return error
```

## Performance Optimization

### Response Time Breakdown
```
Total: ~150ms average

├─ Network Latency: ~30ms
├─ Laravel Processing: ~50ms
│  ├─ Route dispatch: 5ms
│  ├─ Controller: 20ms
│  ├─ Service logic: 20ms
│  └─ Database: 5ms
├─ Event Broadcasting: ~10ms
│  ├─ Serialization: 2ms
│  ├─ Redis write: 3ms
│  └─ Event delivery: 5ms
├─ Reverb Processing: ~20ms
│  ├─ Message parsing: 5ms
│  ├─ Channel routing: 8ms
│  └─ Client delivery: 7ms
└─ Client Processing: ~40ms
   ├─ WebSocket receipt: 5ms
   ├─ Vue update: 30ms
   └─ DOM render: 5ms
```

### Caching Strategy
```
Redis Cache Layers:
1. Query Cache (5 min)
   └─ Expensive aggregations

2. Config Cache (persistent)
   └─ Application configuration

3. Route Cache (persistent)
   └─ Route definitions

4. View Cache (persistent)
   └─ Compiled Blade templates
```

## Scalability Considerations

### Horizontal Scaling
```
Load Balancer
    │
    ├─ App Server 1
    ├─ App Server 2
    ├─ App Server 3
    └─ App Server N
    
    All connect to:
    ├─ MySQL (replication)
    ├─ Redis Cluster
    └─ Reverb Cluster
```

### Limits & Capacity
```
Per Instance (2 CPU, 4GB RAM):
- Concurrent Users: 1,000+
- Requests/sec: 500+
- Database Connections: 100

With Clustering (3 instances):
- Concurrent Users: 10,000+
- Requests/sec: 5,000+
- Database Connections: 300+
```

## Security Architecture

### Data Flow Security
```
User Browser
    ↓ (HTTPS/WSS in production)
Nginx (SSL Termination)
    ↓
Laravel App
├─ Input Validation
├─ CSRF Protection
├─ Rate Limiting
└─ Authorization Checks
    ↓
Database (Prepared Statements)
```

### Channel Authorization
```
WebSocket Subscription Request
    ↓
Reverb validates socket
    ↓
Call authorization callback
    ↓
Verify user identity
    ├─ Token validation
    ├─ Session check
    └─ Permission check
    ↓
Grant/Deny access to channel
```

### Authentication Flow
```
In Development/Testing:
- No explicit authentication required
- Public test user endpoint
- Channel authorization simplified

In Production:
- JWT or Session-based authentication
- Strict permission checks
- Encrypted WebSocket channels
- Rate limiting
- CORS validation
```

## Monitoring & Observability

### Key Metrics to Monitor
```
Performance:
- API response time (p50, p95, p99)
- WebSocket delivery latency
- Database query time
- Queue processing time

Availability:
- API uptime (%)
- WebSocket connection success rate
- Database availability
- Queue success rate

Capacity:
- Active WebSocket connections
- Queue depth
- Redis memory usage
- Database connections in use
```

### Logging Strategy
```
Application Logs (Laravel):
- Location: storage/logs/laravel.log
- Level: INFO, WARNING, ERROR
- Rotation: Daily

WebSocket Logs (Reverb):
- Track connections
- Log subscriptions
- Monitor errors

Queue Logs (Redis):
- Track job processing
- Monitor retries
- Log failures
```

## Disaster Recovery

### Backup Strategy
```
Database:
- Daily full backup
- Hourly incremental backup
- Retention: 30 days
- Store: Off-site

Redis:
- RDB snapshots
- AOF (Append-Only File)
- Replication
- Retention: 7 days
```

### Failover Process
```
1. Detect failure (monitoring alert)
2. Evaluate impact (service critical?)
3. Initiate failover
4. Switch to backup service
5. Restore from backup (if needed)
6. Verify functionality
7. Post-incident review
```
