#!/usr/bin/env bash
set -euo pipefail

cd /app

if [[ ! -f composer.json ]]; then
  echo "Erro: composer.json não encontrado em /app. Verifique BACKEND_PATH."
  exit 1
fi

if [[ ! -f vendor/autoload.php ]]; then
  echo "Dependências PHP ausentes; executando composer install..."
  composer install --no-interaction --no-progress --prefer-dist
fi

echo "Aguardando MySQL em ${MYSQL_HOST:-mysql}:${MYSQL_PORT:-3306}..."
for attempt in $(seq 1 60); do
  if php -r 'try { new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("MYSQL_HOST"), getenv("MYSQL_PORT"), getenv("MYSQL_DATABASE")), getenv("MYSQL_USER"), getenv("MYSQL_PASSWORD")); } catch (Throwable $e) { exit(1); }'; then
    echo "MySQL disponível."
    break
  fi
  if [[ "$attempt" -eq 60 ]]; then
    echo "Erro: MySQL não ficou disponível em 120 segundos."
    exit 1
  fi
  sleep 2
done

mkdir -p var/cache var/log
exec "$@"
