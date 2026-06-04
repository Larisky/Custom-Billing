# API Testing Guide

## Using curl

### 1. Get Test User

```bash
curl -X GET http://localhost/api/users/test | jq
```

Response:
```json
{
  "id": 1,
  "name": "Test User",
  "email": "test@billing.local",
  "balance": 0
}
```

### 2. Initiate Payment

```bash
USER_ID=1
AMOUNT=100.00

curl -X POST http://localhost/api/users/$USER_ID/payments \
  -H "Content-Type: application/json" \
  -d "{\"amount\": $AMOUNT, \"payment_method\": \"card\"}" | jq
```

Response:
```json
{
  "payment_id": 1,
  "status": "pending",
  "amount": "100.00",
  "reference_id": "REF-xxx",
  "message": "Payment initialized successfully"
}
```

### 3. Process Payment

```bash
USER_ID=1
PAYMENT_ID=1

curl -X POST http://localhost/api/users/$USER_ID/payments/$PAYMENT_ID/process | jq
```

Response (Success):
```json
{
  "payment_id": 1,
  "status": "success",
  "amount": "100.00",
  "user_balance": "100.00",
  "timestamp": "2024-01-15T10:30:45Z"
}
```

### 4. Get Payment Status

```bash
curl -X GET http://localhost/api/users/$USER_ID/payments/$PAYMENT_ID | jq
```

### 5. Get Payment History

```bash
curl -X GET http://localhost/api/users/$USER_ID/payments | jq
```

### 6. Refund Payment

```bash
curl -X POST http://localhost/api/users/$USER_ID/payments/$PAYMENT_ID/refund | jq
```

## Using Postman

1. **Create Collection**: Custom Billing
2. **Add Environment Variables**:
   - `base_url`: http://localhost
   - `user_id`: 1

3. **Create Requests**:

### GET /api/users/test
- URL: `{{base_url}}/api/users/test`
- Method: GET

### POST /api/users/{{user_id}}/payments
- URL: `{{base_url}}/api/users/{{user_id}}/payments`
- Method: POST
- Body (JSON):
```json
{
  "amount": 100.00,
  "payment_method": "card"
}
```

### POST /api/users/{{user_id}}/payments/:paymentId/process
- URL: `{{base_url}}/api/users/{{user_id}}/payments/{{payment_id}}/process`
- Method: POST

## Load Testing

### Using Apache Bench (ab)

```bash
# Simulate 100 requests with 10 concurrent connections
ab -n 100 -c 10 http://localhost/api/health

# GET endpoint
ab -n 1000 -c 50 http://localhost/api/users/test

# POST endpoint with data
ab -n 100 -c 10 -p data.json -T application/json \
   http://localhost/api/users/1/payments
```

### Using wrk

```bash
# Install wrk
brew install wrk  # macOS
# or apt-get install wrk  # Linux

# Run load test
wrk -t4 -c100 -d30s http://localhost/api/users/test

# With custom script
wrk -t4 -c100 -d30s -s script.lua http://localhost/api/users/1/payments
```

### Using Apache JMeter

1. Create Thread Group
2. Add HTTP Request Sampler
3. Set:
   - Server Name: localhost
   - Port: 80
   - Path: /api/users/test
4. Run test

## WebSocket Testing

### Using WebSocket Cat (wscat)

```bash
# Install
npm install -g wscat

# Connect
wscat -c ws://localhost:8080

# Send subscription
{
  "event": "pusher:subscribe",
  "data": {
    "channel": "payment.user.1"
  }
}
```

### Using Browser Console

```javascript
// Connect to WebSocket
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = () => {
  console.log('Connected');
  ws.send(JSON.stringify({
    event: 'pusher:subscribe',
    data: { channel: 'payment.user.1' }
  }));
};

ws.onmessage = (event) => {
  console.log('Message:', event.data);
};

ws.onerror = (error) => {
  console.error('Error:', error);
};
```

## Stress Testing

```bash
# Generate 1000 concurrent connections
for i in {1..1000}; do
  curl http://localhost/api/health &
done
wait

# Monitor resource usage
watch 'docker stats --no-stream'

# Kill all curl processes
killall curl
```

## Monitoring

### Real-time Dashboard

```bash
# Monitor all containers
docker stats

# Monitor specific container
docker stats billing_app

# View logs with timestamps
docker-compose logs -f --timestamps
```

### Performance Metrics

```bash
# Get container memory usage
docker stats billing_app --no-stream

# Get MySQL slow queries
docker-compose exec mysql mysql -ubilling_user -pbilling_password -e "SELECT * FROM mysql.slow_log\G"

# Get Redis memory info
docker-compose exec redis redis-cli INFO memory

# Check database query count
docker-compose exec app php artisan tinker
>>> DB::table('payments')->count()
```

## Debugging

### Enable Query Logging

```bash
# In backend/.env
DB_LOG_QUERIES=true

# View logs
docker-compose logs -f app | grep "SELECT\|INSERT\|UPDATE"
```

### Enable Event Logging

```bash
# In Laravel code
Event::listen(function (PaymentStatusChanged $event) {
    \Log::info('Payment event dispatched', $event->broadcastWith());
});
```

### Monitor WebSocket

```bash
# Enable Reverb debug mode
docker-compose exec reverb php artisan reverb:start --debug

# Watch WebSocket connections
docker-compose logs -f reverb | grep -i "subscription\|connection"
```
