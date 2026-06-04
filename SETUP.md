# Setup & Installation Guide

## System Requirements

### Minimum Requirements
- CPU: 2 cores
- RAM: 4GB
- Disk: 10GB
- Docker: v20.10+
- Docker Compose: v1.29+

### Recommended for Development
- CPU: 4+ cores
- RAM: 8GB+
- Disk: 20GB+
- Latest Docker & Docker Compose

## Pre-Installation Checklist

```bash
# Verify Docker installation
docker --version      # Should be v20.10+
docker-compose --version  # Should be v1.29+
docker run hello-world    # Should print "Hello from Docker!"

# Verify Git
git --version

# Verify Node.js (optional, for local frontend dev)
node --version
npm --version
```

## Installation Steps

### Step 1: Clone Repository

```bash
git clone <repository-url> custom-billing
cd custom-billing
```

### Step 2: Configure Environment

Backend configuration is pre-set in `.env`. To customize:

```bash
cd backend
cp .env.example .env

# Edit .env if needed
nano .env  # or use your preferred editor
```

Default values (good for development):
```env
APP_ENV=local
APP_DEBUG=true
DB_HOST=mysql
REDIS_HOST=redis
REVERB_HOST=reverb
```

### Step 3: Build & Start Docker Services

```bash
# From project root
cd ../

# Build images (first time)
docker-compose build

# Start all services
docker-compose up -d

# Verify services are running
docker-compose ps
```

Expected output:
```
NAME              STATUS              PORTS
billing_nginx     Up                   0.0.0.0:80->80/tcp
billing_app       Up                   9000/tcp
billing_mysql     Up                   3306/tcp
billing_redis     Up                   6379/tcp
billing_reverb    Up                   0.0.0.0:8080->8080/tcp
billing_queue     Up
```

### Step 4: Initialize Backend

```bash
# Wait for MySQL to be ready (~10 seconds)
docker-compose exec app php artisan migrate

# Create test data (optional)
docker-compose exec app php artisan db:seed

# Verify setup
docker-compose exec app php artisan tinker
>>> DB::table('users')->count()
```

### Step 5: Setup Frontend

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

The frontend will be available at: **http://localhost:5173**

### Step 6: Verify Installation

```bash
# Check Laravel is working
curl -s http://localhost/api/health | jq .

# Check WebSocket server
curl -s http://localhost:8080 # Should show Reverb server

# Check Redis
docker-compose exec redis redis-cli PING

# List users
curl -s http://localhost/api/users/test | jq .
```

## Successful Installation Indicators

✅ All containers running:
```bash
docker-compose ps | grep Up
```

✅ Laravel app accessible:
```bash
curl http://localhost/api/health
```

✅ Frontend loads:
```
http://localhost:5173 (shows balance and payment cards)
```

✅ WebSocket connects:
```
DevTools → Network → WS → Should show ws://localhost:8080
```

✅ Database ready:
```bash
docker-compose exec mysql mysql -ubilling_user -pbilling_password billing_db -e "SHOW TABLES;"
```

## Common Issues & Solutions

### Issue 1: Port Already in Use

```bash
# Port 80 in use
sudo lsof -i :80
sudo kill -9 <PID>

# Port 3306 in use
sudo lsof -i :3306
docker-compose down

# Port 6379 in use
sudo lsof -i :6379
redis-cli SHUTDOWN
```

### Issue 2: Docker Service Won't Start

```bash
# Restart Docker daemon
sudo systemctl restart docker

# Check Docker logs
docker logs <container-name>

# Rebuild containers
docker-compose down
docker system prune -a
docker-compose build --no-cache
docker-compose up -d
```

### Issue 3: MySQL Connection Failed

```bash
# Wait for MySQL to be ready
sleep 30 && docker-compose up -d

# Check MySQL logs
docker-compose logs mysql

# Force recreation
docker-compose down -v
docker-compose up -d
```

### Issue 4: Frontend Can't Connect to API

```bash
# Verify VITE environment variables
grep VITE frontend/.env

# Check proxy in vite.config.js is correct
# Should point to http://localhost/api

# Restart frontend dev server
cd frontend
npm run dev
```

### Issue 5: WebSocket Not Connecting

```bash
# Check Reverb is running
docker-compose logs reverb

# Verify WebSocket endpoint
# Frontend should connect to ws://localhost:8080

# Check firewall
sudo ufw allow 8080
```

## Production Deployment

For deployment to production servers:

1. **Update Environment**:
```bash
cp backend/.env.example backend/.env.production
# Edit with production values:
# - APP_ENV=production
# - APP_DEBUG=false
# - DB_HOST=prod-db-server
# - REVERB_HOST=prod-reverb-server
# - REVERB_SCHEME=https
```

2. **Build Frontend**:
```bash
cd frontend
npm run build
# Copy dist/ to backend/public/
```

3. **SSL/TLS Setup**:
```bash
# Use Let's Encrypt with certbot
sudo certbot certonly --standalone -d billing.example.com
# Update nginx config to use certificates
```

4. **Database Backups**:
```bash
# Create backup
docker-compose exec mysql mysqldump -ubilling_user -pbilling_password billing_db > backup.sql

# Schedule regular backups (cron job)
0 2 * * * docker-compose exec -T mysql mysqldump -ubilling_user -pbilling_password billing_db > /backups/billing_$(date +\%Y\%m\%d).sql
```

5. **Monitoring Setup**:
```bash
# Monitor container health
docker stats

# View logs
docker-compose logs -f

# Set up centralized logging (optional)
# Use ELK stack or similar
```

## Optimization

### Frontend Optimization

```bash
# Build optimized production version
cd frontend
npm run build

# Analyze bundle size
npm install -D rollup-plugin-visualizer
# Update vite.config.js to include analyzer
```

### Backend Optimization

```bash
# Cache configuration
docker-compose exec app php artisan config:cache

# Cache routes
docker-compose exec app php artisan route:cache

# Cache views
docker-compose exec app php artisan view:cache

# Precompile autoloader
docker-compose exec app composer install --optimize-autoloader
```

### Database Optimization

```bash
# Add indexes (migrations already include them)
# Run ANALYZE TABLE for query optimization
docker-compose exec mysql mysql -ubilling_user -pbilling_password -e "ANALYZE TABLE billing_db.users; ANALYZE TABLE billing_db.payments;"

# Monitor slow queries
# Enable slow query log in MySQL config
```

### Redis Optimization

```bash
# Configure persistence
# Edit docker-compose.yml for Redis command

# Monitor memory usage
docker-compose exec redis redis-cli INFO memory

# Clean expired keys
docker-compose exec redis redis-cli FLUSHDB
```

## Maintenance

### Regular Tasks

```bash
# Daily: Check logs
docker-compose logs --tail=100

# Weekly: Database backup
docker-compose exec -T mysql mysqldump -ubilling_user -pbilling_password billing_db > backups/backup_weekly.sql

# Monthly: Update dependencies
cd backend && composer update
cd ../frontend && npm update

# Quarterly: Security updates
docker-compose pull
docker-compose build --pull
docker-compose up -d
```

### Health Checks

```bash
# Create health check script
cat > health-check.sh << 'EOF'
#!/bin/bash
echo "Checking services..."
curl -f http://localhost/api/health || exit 1
curl -f ws://localhost:8080 || exit 1
docker-compose exec -T mysql ping -c 1 || exit 1
docker-compose exec -T redis redis-cli PING || exit 1
echo "All services healthy!"
EOF

# Run daily via cron
0 */4 * * * /path/to/health-check.sh
```

## Cleanup

If you need to completely reset:

```bash
# Stop all services
docker-compose down

# Remove volumes (data will be deleted!)
docker-compose down -v

# Remove all containers
docker-compose down --remove-orphans

# Clean up Docker system
docker system prune -a

# Reinstall from scratch
docker-compose build
docker-compose up -d
docker-compose exec app php artisan migrate
```

## Next Steps

1. ✅ Run the application
2. ✅ Test payment flows
3. ✅ Review test coverage
4. ✅ Deploy to production
5. ✅ Monitor in production

For more information, see [README.md](./README.md)
