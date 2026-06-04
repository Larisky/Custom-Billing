#!/bin/bash

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if Docker is running
check_docker() {
    if ! docker ps > /dev/null 2>&1; then
        log_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
    log_success "Docker is running"
}

# Start services
start_services() {
    log_info "Starting Docker services..."
    docker-compose up -d
    
    # Wait for MySQL to be ready
    log_info "Waiting for MySQL to be ready..."
    for i in {1..30}; do
        if docker-compose exec -T mysql mysqladmin ping -ubilling_user -pbilling_password > /dev/null 2>&1; then
            log_success "MySQL is ready"
            break
        fi
        if [ $i -eq 30 ]; then
            log_error "MySQL failed to start"
            exit 1
        fi
        sleep 1
    done
    
    log_success "All services started"
}

# Run migrations
run_migrations() {
    log_info "Running database migrations..."
    docker-compose exec app php artisan migrate --force
    log_success "Migrations completed"
}

# Run tests
run_tests() {
    log_info "Running backend tests..."
    docker-compose exec app php artisan test
    log_success "Backend tests completed"
    
    log_info "Running frontend tests..."
    cd frontend && npm test && cd ..
    log_success "Frontend tests completed"
}

# Show status
show_status() {
    log_info "Service Status:"
    docker-compose ps
}

# Show logs
show_logs() {
    if [ -z "$1" ]; then
        docker-compose logs -f
    else
        docker-compose logs -f "$1"
    fi
}

# Setup initial data
setup_data() {
    log_info "Seeding test data..."
    docker-compose exec app php artisan db:seed
    log_success "Test data created"
}

# Full setup
full_setup() {
    check_docker
    start_services
    run_migrations
    setup_data
    log_success "Full setup completed!"
    echo ""
    echo -e "${GREEN}You can now access:${NC}"
    echo "  Frontend: ${BLUE}http://localhost:5173${NC}"
    echo "  API: ${BLUE}http://localhost/api${NC}"
    echo "  WebSocket: ${BLUE}ws://localhost:8080${NC}"
}

# Main menu
if [ -z "$1" ]; then
    echo "Custom Billing System - Helper Script"
    echo ""
    echo "Usage: ./setup.sh [command]"
    echo ""
    echo "Commands:"
    echo "  setup         - Full setup (Docker + migrations + seed data)"
    echo "  start         - Start Docker services"
    echo "  stop          - Stop Docker services"
    echo "  restart       - Restart Docker services"
    echo "  migrate       - Run database migrations"
    echo "  seed          - Seed test data"
    echo "  test          - Run all tests"
    echo "  test-backend  - Run backend tests only"
    echo "  test-frontend - Run frontend tests only"
    echo "  status        - Show service status"
    echo "  logs          - Show service logs (add service name for specific log)"
    echo "  shell         - SSH into app container"
    echo "  clean         - Stop and remove all containers"
    echo "  clean-hard    - Hard reset (removes volumes too)"
    exit 0
fi

case "$1" in
    setup)
        full_setup
        ;;
    start)
        check_docker
        log_info "Starting services..."
        docker-compose up -d
        log_success "Services started"
        ;;
    stop)
        log_info "Stopping services..."
        docker-compose down
        log_success "Services stopped"
        ;;
    restart)
        log_info "Restarting services..."
        docker-compose restart
        log_success "Services restarted"
        ;;
    migrate)
        run_migrations
        ;;
    seed)
        setup_data
        ;;
    test)
        run_tests
        ;;
    test-backend)
        log_info "Running backend tests..."
        docker-compose exec app php artisan test
        ;;
    test-frontend)
        log_info "Running frontend tests..."
        cd frontend && npm test && cd ..
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs "$2"
        ;;
    shell)
        docker-compose exec app bash
        ;;
    clean)
        log_warning "Stopping and removing containers..."
        docker-compose down
        log_success "Cleanup completed"
        ;;
    clean-hard)
        log_warning "HARD RESET: Removing all containers and volumes..."
        docker-compose down -v
        log_success "Hard reset completed"
        ;;
    *)
        log_error "Unknown command: $1"
        exit 1
        ;;
esac
