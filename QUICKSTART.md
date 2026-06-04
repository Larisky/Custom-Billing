# Quick Start Reference Card

## 🎯 5-Minute Setup

```bash
# 1. Start Docker services
docker-compose up -d

# 2. Wait ~15 seconds for MySQL
sleep 15

# 3. Run migrations
docker-compose exec app php artisan migrate

# 4. In another terminal, start frontend
cd frontend && npm install && npm run dev

# 5. Open browser to http://localhost:5173
```

## 🧪 Testing in 2 Minutes

```bash
# Backend tests
docker-compose exec app php artisan test

# Frontend tests
cd frontend && npm test

# With coverage
docker-compose exec app php artisan test --coverage
```

## 📱 Test the API

```bash
# Get test user
curl http://localhost/api/users/test | jq

# Initialize payment (use userId from above, e.g., 1)
curl -X POST http://localhost/api/users/1/payments \
  -H "Content-Type: application/json" \
  -d '{"amount": 100, "payment_method": "card"}' | jq

# Process payment
curl -X POST http://localhost/api/users/1/payments/1/process | jq

# Check updated balance
curl http://localhost/api/users/1/balance | jq
```

## 🔧 Common Commands

| Task | Command |
|------|---------|
| View logs | `docker-compose logs -f [service]` |
| SSH into app | `docker-compose exec app bash` |
| Run artisan | `docker-compose exec app php artisan [cmd]` |
| Access MySQL | `docker-compose exec mysql mysql -ubilling_user -pbilling_password billing_db` |
| Redis CLI | `docker-compose exec redis redis-cli` |
| Stop services | `docker-compose down` |
| Restart services | `docker-compose restart` |
| Hard reset | `docker-compose down -v` |
| Full rebuild | `docker-compose build --no-cache && docker-compose up -d` |

## 📍 URLs

| Service | URL |
|---------|-----|
| Frontend | http://localhost:5173 |
| API | http://localhost/api |
| WebSocket | ws://localhost:8080 |
| API Health | http://localhost/api/health |

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Port already in use | `docker-compose down && docker system prune -a` |
| MySQL won't connect | Wait 30s: `sleep 30 && docker-compose exec app php artisan migrate` |
| Frontend can't find API | Check VITE_API_URL in vite.config.js |
| WebSocket not connecting | Check browser console, verify ws://localhost:8080 |
| Tests fail | Run `docker-compose exec app php artisan config:cache` |

## 📝 File Locations

| Component | Location |
|-----------|----------|
| API Routes | `backend/routes/api.php` |
| Models | `backend/app/Models/` |
| Controllers | `backend/app/Http/Controllers/` |
| Events | `backend/app/Events/` |
| Services | `backend/app/Services/` |
| Migrations | `backend/database/migrations/` |
| Tests | `backend/tests/` |
| Vue Components | `frontend/src/components/` |
| API Client | `frontend/src/services/api.js` |
| WebSocket Client | `frontend/src/services/websocket.js` |

## 🔍 Key Files

- `docker-compose.yml` - Service definitions
- `backend/.env` - Backend configuration
- `frontend/vite.config.js` - Frontend build config
- `backend/config/broadcasting.php` - Reverb config
- `backend/routes/channels.php` - WebSocket auth
- `README.md` - Main documentation

## 💡 Remember

- Frontend runs on port 5173 (Vite dev server)
- API runs on port 80 (Nginx)
- WebSocket runs on port 8080 (Reverb)
- Database on port 3306 (MySQL)
- Redis on port 6379

## 📚 Full Documentation

- **Architecture**: See [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Setup Details**: See [SETUP.md](./SETUP.md)
- **Development**: See [DEVELOPMENT.md](./DEVELOPMENT.md)
- **Testing**: See [TESTING.md](./TESTING.md)
- **Main Docs**: See [README.md](./README.md)

## 🚀 First-Time Success Checklist

- [ ] Docker running
- [ ] Services started (`docker-compose ps` shows all Up)
- [ ] Migrations ran (`docker-compose logs app | grep migrat`)
- [ ] Frontend installed (`cd frontend && ls node_modules | head`)
- [ ] Frontend dev server running (`http://localhost:5173` loads)
- [ ] Test user loaded (shows "Test User" and balance $0)
- [ ] API working (`curl http://localhost/api/health`)
- [ ] WebSocket connected (DevTools → Network → WS connected)

## ⏱️ Expected Timing

- Setup: 2-3 minutes
- First test run: 30 seconds
- Full test suite: 1-2 minutes
- Payment flow: 5-10 seconds
- Real-time update: <500ms

---

**Need help?** Check the relevant documentation file or see troubleshooting section above.
