SHELL := /bin/sh
.DEFAULT_GOAL := help

INFRA_DIR ?= ../safe-score-infra
INFRA_ENV ?= $(INFRA_DIR)/.env
PHP_SERVICE ?= backend

COMPOSE := docker compose --project-directory $(INFRA_DIR) --env-file $(INFRA_ENV) -f $(INFRA_DIR)/compose.yaml -f $(INFRA_DIR)/compose.override.yaml
EXEC := $(COMPOSE) exec -T
PHP := $(EXEC) $(PHP_SERVICE) php
TEST_PHP := $(COMPOSE) exec -T -e APP_ENV=test $(PHP_SERVICE) php
COMPOSER := $(EXEC) $(PHP_SERVICE) composer
CONSOLE := $(PHP) bin/console
TEST_CONSOLE := $(TEST_PHP) bin/console
ASSERT_NOT_PROD := $(EXEC) $(PHP_SERVICE) sh -c 'test "$$APP_ENV" != "prod" || { echo "Operação bloqueada: APP_ENV=prod." >&2; exit 1; }'

.PHONY: help env-check \
	up up-build down restart stop ps logs logs-backend \
	install composer-install composer-update \
	console cache-clear cache-warmup routes about debug-container debug-router \
	db-create db-drop migration migrate migrate-status migrate-prev schema-validate doctrine-mapping \
	test test-file coverage \
	lint lint-fix phpstan cs-check cs-fix quality \
	fixtures test-db-reset shell root-shell

##@ Ajuda
help: ## Exibe esta ajuda
	@awk 'BEGIN { FS = ":.*##"; printf "Uso: make <target> [parametro=valor]\n" } /^##@/ { printf "\n%s:\n", substr($$0, 5) } /^[a-zA-Z0-9_.-]+:.*##/ { printf "  %-20s %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

##@ Ambiente
env-check: ## Valida os pré-requisitos e arquivos da infraestrutura local
	@command -v docker >/dev/null 2>&1 || { echo "Erro: docker não está disponível." >&2; exit 1; }
	@docker compose version >/dev/null 2>&1 || { echo "Erro: Docker Compose v2 não está disponível." >&2; exit 1; }
	@test -f "$(INFRA_DIR)/compose.yaml" || { echo "Erro: $(INFRA_DIR)/compose.yaml não encontrado." >&2; exit 1; }
	@test -f "$(INFRA_ENV)" || { echo "Erro: $(INFRA_ENV) não encontrado. Crie-o a partir de $(INFRA_DIR)/.env.example." >&2; exit 1; }
	@echo "Infraestrutura local válida em $(INFRA_DIR)."

##@ Infraestrutura
up: env-check ## Sobe os serviços em background
	$(COMPOSE) up -d

up-build: env-check ## Sobe os serviços em background reconstruindo as imagens
	$(COMPOSE) up -d --build

down: env-check ## Derruba os serviços preservando os volumes
	$(COMPOSE) down

restart: env-check ## Reinicia os serviços
	$(COMPOSE) restart

stop: env-check ## Para os serviços sem removê-los
	$(COMPOSE) stop

ps: env-check ## Lista o estado dos serviços
	$(COMPOSE) ps

logs: env-check ## Acompanha os logs de todos os serviços
	$(COMPOSE) logs -f

logs-backend: env-check ## Acompanha os logs do backend
	$(COMPOSE) logs -f $(PHP_SERVICE)

##@ Dependências
install: env-check ## Sobe o ambiente e instala as dependências PHP
	$(COMPOSE) up -d
	$(COMPOSER) install --no-interaction --prefer-dist

composer-install: env-check ## Instala as dependências PHP conforme composer.lock
	$(COMPOSER) install --no-interaction --prefer-dist

composer-update: env-check ## Atualiza as dependências PHP e o composer.lock
	$(COMPOSER) update

##@ Symfony
console: env-check ## Executa o console Symfony; use cmd="debug:container"
	$(CONSOLE) $(cmd)

cache-clear: env-check ## Limpa o cache do Symfony
	$(CONSOLE) cache:clear

cache-warmup: env-check ## Aquece o cache do Symfony
	$(CONSOLE) cache:warmup

routes: env-check ## Lista as rotas da aplicação
	$(CONSOLE) debug:router

about: env-check ## Exibe informações da aplicação e do ambiente
	$(CONSOLE) about

debug-container: env-check ## Lista os serviços do container Symfony
	$(CONSOLE) debug:container

debug-router: routes ## Alias explícito para listar as rotas

##@ Banco e migrations
db-create: env-check ## Cria o banco local caso ainda não exista
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop: env-check ## APAGA o banco local; exige confirm=YES
	@test "$(confirm)" = "YES" || { echo "Operação cancelada. Use: make db-drop confirm=YES" >&2; exit 1; }
	$(ASSERT_NOT_PROD)
	$(CONSOLE) doctrine:database:drop --force --if-exists

migration: env-check ## Gera uma migration a partir do diff do Doctrine
	$(CONSOLE) doctrine:migrations:diff

migrate: env-check ## Executa todas as migrations pendentes
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

migrate-status: env-check ## Exibe o estado das migrations
	$(CONSOLE) doctrine:migrations:status

migrate-prev: env-check ## REVERTE somente a migration anterior; exige confirm=YES
	@test "$(confirm)" = "YES" || { echo "Operação cancelada. Use: make migrate-prev confirm=YES" >&2; exit 1; }
	$(ASSERT_NOT_PROD)
	$(CONSOLE) doctrine:migrations:migrate prev --no-interaction

schema-validate: env-check ## Valida o mapeamento e a sincronização do schema
	$(CONSOLE) doctrine:schema:validate

doctrine-mapping: env-check ## Exibe as entidades reconhecidas pelo Doctrine
	$(CONSOLE) doctrine:mapping:info

##@ Testes
test: env-check ## Executa a suíte PHPUnit configurada via Composer
	$(COMPOSER) test

test-file: env-check ## Executa um arquivo de teste; use file="tests/Caminho/Test.php"
	@test -n "$(file)" || { echo "Informe o arquivo: make test-file file=tests/Caminho/Test.php" >&2; exit 1; }
	$(COMPOSER) test -- "$(file)"

coverage: env-check ## Executa PHPUnit com relatório de cobertura em texto
	$(PHP) vendor/bin/phpunit --coverage-text

##@ Qualidade
lint: env-check ## Valida a sintaxe dos arquivos PHP
	$(EXEC) $(PHP_SERVICE) sh -lc 'find src tests migrations config -type f -name "*.php" -print0 | xargs -0 -n1 php -l'

lint-fix: cs-fix ## Corrige automaticamente a formatação PHP

phpstan: env-check ## Executa a análise estática configurada via Composer
	$(COMPOSER) analyse

cs-check: env-check ## Verifica a formatação configurada via Composer
	$(COMPOSER) cs-check

cs-fix: env-check ## Corrige a formatação configurada via Composer
	$(COMPOSER) cs-fix

quality: lint ## Executa lint, PHPStan, style check e testes
	$(COMPOSER) check

##@ Fixtures e teste
fixtures: env-check ## RECARREGA fixtures no banco local; exige confirm=YES
	@test "$(confirm)" = "YES" || { echo "Operação cancelada. Use: make fixtures confirm=YES" >&2; exit 1; }
	$(ASSERT_NOT_PROD)
	$(CONSOLE) doctrine:fixtures:load --no-interaction

test-db-reset: env-check ## RECRIA banco de teste, migra e carrega fixtures; exige confirm=YES
	@test "$(confirm)" = "YES" || { echo "Operação cancelada. Use: make test-db-reset confirm=YES" >&2; exit 1; }
	$(TEST_CONSOLE) doctrine:database:drop --force --if-exists
	$(TEST_CONSOLE) doctrine:database:create
	$(TEST_CONSOLE) doctrine:migrations:migrate --no-interaction
	$(TEST_CONSOLE) doctrine:fixtures:load --no-interaction

##@ Shell
shell: env-check ## Abre um shell como o usuário da aplicação
	$(COMPOSE) exec $(PHP_SERVICE) sh

root-shell: env-check ## Abre um shell como root no backend
	$(COMPOSE) exec --user root $(PHP_SERVICE) sh
