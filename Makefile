SHELL := /bin/sh
DC := docker compose
APP := $(DC) exec app

.PHONY: up down build restart logs ps shell migrate seed fresh test test-filter pint npm db adminer mail artisan composer

up: ## Levanta todo el stack
	$(DC) up -d --build

down: ## Detiene los servicios
	$(DC) down

build: ## Reconstruye las imágenes
	$(DC) build --no-cache

restart: ## Reinicia los servicios
	$(DC) restart

logs: ## Muestra logs en vivo
	$(DC) logs -f --tail=200

ps: ## Estado de los contenedores
	$(DC) ps

shell: ## Shell dentro del contenedor app
	$(DC) exec app bash

migrate: ## Ejecuta migraciones
	$(APP) php artisan migrate

seed: ## Ejecuta seeders
	$(APP) php artisan db:seed

fresh: ## Recrea la base de datos con seeders (destructivo)
	$(APP) php artisan migrate:fresh --seed

test: ## Ejecuta la suite de pruebas
	$(APP) php artisan test

test-filter: ## Ejecuta pruebas filtradas: make test-filter FILTER=Nombre
	$(APP) php artisan test --filter=$(FILTER)

pint: ## Formatea el código con Pint
	$(APP) vendor/bin/pint

npm: ## Compila los assets
	$(APP) npm run build

db: ## Consola psql
	$(DC) exec db psql -U $${POSTGRES_USER:-criptografia} -d $${POSTGRES_DB:-criptografia}

adminer: ## Levanta Adminer opcional en 8081
	$(DC) --profile tools up -d adminer

mail: ## Muestra la URL de Mailpit
	@echo "Mailpit: http://localhost:$${MAILPIT_UI_PORT:-8025}"

artisan: ## Ejecuta artisan: make artisan CMD="route:list"
	$(APP) php artisan $(CMD)

composer: ## Ejecuta composer: make composer CMD="require paquete"
	$(APP) composer $(CMD)
