# ----------------------------------------
# Имя сервиса Docker Compose
DC=docker compose
# Контейнер приложения
APP=app
# ----------------------------------------
# Build Docker images
# ----------------------------------------
.PHONY: build
build:
	@echo "🛠️  Building Docker images..."
	$(DC) build --pull

# ----------------------------------------
# Up (запуск контейнеров)
# ----------------------------------------
.PHONY: up
up:
	@echo "🚀  Starting Docker environment..."
	$(DC) up -d

# ----------------------------------------
# Up + Build
# ----------------------------------------
.PHONY: rebuild
rebuild: build up
	@echo "✅  Docker environment rebuilt and started."

# ----------------------------------------
# Stop containers
# ----------------------------------------
.PHONY: down
down:
	@echo "🛑  Stopping Docker environment..."
	$(DC) down

# ----------------------------------------
# Stop and remove volumes
# ----------------------------------------
.PHONY: down-v
down-v:
	@echo "🛑  Stopping Docker environment and removing volumes..."
	$(DC) down -v

# ----------------------------------------
# Restart all containers
# ----------------------------------------
.PHONY: restart
restart:
	@echo "🔄  Restarting all containers..."
	$(DC) restart

# ----------------------------------------
# Show container status
# ----------------------------------------
.PHONY: ps
ps:
	@echo "📊  Container status..."
	$(DC) ps

# ----------------------------------------
# Enter application container
# ----------------------------------------
.PHONY: shell
shell:
	@echo "💻  Entering application container..."
	$(DC) exec $(APP) sh

# ----------------------------------------
# Enter specific container (usage: make exec service=postgres)
# ----------------------------------------
.PHONY: exec
exec:
	@echo "💻  Entering $(service) container..."
	$(DC) exec $(service) sh

# ----------------------------------------
# Laravel artisan commands
# ----------------------------------------
.PHONY: artisan
artisan:
	@echo "⚡ Running artisan command..."
	$(DC) exec $(APP) php artisan $(filter-out $@,$(MAKECMDGOALS))

# ----------------------------------------
# Enter app container and run pnpm dev
# ----------------------------------------
.PHONY: dev
dev:
	@echo "🚀 Starting pnpm dev inside app container..."
	$(DC) exec $(APP) sh -c "pnpm dev"

# ----------------------------------------
# Composer commands
# ----------------------------------------
.PHONY: composer
composer:
	@echo "📦 Running composer command..."
	$(DC) exec $(APP) composer $(filter-out $@,$(MAKECMDGOALS))

# ----------------------------------------
# Tail logs (all containers)
# ----------------------------------------
.PHONY: logs
logs:
	@echo "📋  Showing logs for all containers..."
	$(DC) logs -f

# ----------------------------------------
# Tail logs for specific service (usage: make logs-service service=app)
# ----------------------------------------
.PHONY: logs-service
logs-service:
	@echo "📋  Showing logs for $(service)..."
	$(DC) logs -f $(service)

# ----------------------------------------
# Show stats for all containers
# ----------------------------------------
.PHONY: stats
stats:
	@echo "📊  Container resource usage..."
	docker stats $$($(DC) ps -q)

# ----------------------------------------
# Prune Docker system
# ----------------------------------------
.PHONY: prune
prune:
	@echo "🧹  Cleaning up Docker system..."
	docker system prune -f

# ----------------------------------------
# Prune everything including volumes
# ----------------------------------------
.PHONY: prune-all
prune-all:
	@echo "🧹  Deep cleaning Docker system..."
	docker system prune -a --volumes -f

# ----------------------------------------
# Help
# ----------------------------------------
.PHONY: help
help:
	@echo "Available commands:"
	@echo "  make build          - Build Docker images"
	@echo "  make up             - Start all containers"
	@echo "  make down           - Stop all containers"
	@echo "  make down-v         - Stop containers and remove volumes"
	@echo "  make restart        - Restart all containers"
	@echo "  make rebuild        - Rebuild and start containers"
	@echo "  make ps             - Show container status"
	@echo "  make shell          - Enter app container"
	@echo "  make exec service=X - Enter specific container"
	@echo "  make logs           - Show logs for all containers"
	@echo "  make logs-service service=X - Show logs for specific container"
	@echo "  make stats          - Show resource usage"
	@echo "  make artisan        - Run Laravel artisan command"
	@echo "  make composer       - Run composer command"
	@echo "  make prune          - Clean Docker system"
	@echo "  make prune-all      - Deep clean Docker system"

%:
	@:
