# Spec 10 — Score API

## Objetivo
Expor score atual, histórico, listagem e recálculo.

## Endpoints

```text
GET /api/v1/customers/{id}/score
GET /api/v1/customers/{id}/score/history
POST /api/v1/customers/{id}/score/recalculate
GET /api/v1/scores
```

## Aceite
Contratos tipados, paginação, filtros, autorização e multi-tenancy.
