# SafeScore — API Contract

## 1. Convenções

Base URL:

```text
/api/v1
```

Formato:

```json
{
  "data": {},
  "meta": {},
  "errors": []
}
```

Datas em ISO 8601.

Valores monetários enviados como string decimal:

```json
{
  "amount": "125000.50",
  "currency": "BRL"
}
```

## 2. Autenticação

### POST /auth/login

Request:

```json
{
  "email": "usuario@empresa.com",
  "password": "senha"
}
```

### POST /auth/logout

### POST /auth/forgot-password

### POST /auth/reset-password

### GET /me

Retorna usuário, organização ativa e permissões.

## 3. Organizações

### GET /organizations/current

### PATCH /organizations/current

### GET /organizations/current/users

### POST /organizations/current/users/invitations

### PATCH /organizations/current/users/{id}

## 4. Clientes

### GET /customers

Filtros:

- search
- status
- over_limit
- overdue
- page
- per_page
- sort

### POST /customers

```json
{
  "legal_name": "Empresa Cliente LTDA",
  "trade_name": "Empresa Cliente",
  "document": "12345678000190",
  "external_id": "CLI-001",
  "segment": "Distribuição"
}
```

### GET /customers/{id}

### PATCH /customers/{id}

### DELETE /customers/{id}

Soft delete.

### GET /customers/{id}/financial-summary

Resposta:

```json
{
  "data": {
    "credit_limit": "500000.00",
    "exposure": "420000.00",
    "available_credit": "80000.00",
    "utilization_percentage": "84.00",
    "overdue_amount": "35000.00",
    "oldest_overdue_days": 42,
    "portfolio_concentration_percentage": "12.50"
  }
}
```

## 5. Limites de crédito

### GET /customers/{id}/credit-limits

### POST /customers/{id}/credit-limits

```json
{
  "amount": "500000.00",
  "valid_from": "2026-07-01",
  "valid_until": null,
  "reason": "Aprovação inicial"
}
```

### PATCH /credit-limits/{id}

### POST /credit-limits/{id}/revoke

## 6. Recebíveis

### GET /receivables

Filtros:

- customer_id
- status
- overdue
- due_date_from
- due_date_to
- aging_bucket
- page
- per_page

### POST /receivables

### GET /receivables/{id}

### PATCH /receivables/{id}

### DELETE /receivables/{id}

## 7. Importações

### POST /imports

Multipart form-data:

- file
- type

### POST /imports/{id}/mapping

```json
{
  "columns": {
    "Cliente": "customer_name",
    "CNPJ": "customer_document",
    "NumeroTitulo": "external_id",
    "Vencimento": "due_date",
    "ValorOriginal": "original_amount",
    "Saldo": "open_amount"
  }
}
```

### POST /imports/{id}/validate

### GET /imports/{id}/preview

### POST /imports/{id}/process

### GET /imports/{id}

### GET /imports/{id}/errors

## 8. Dashboard

### GET /dashboard/summary

Retorna:

- exposição total;
- valor vencido;
- percentual vencido;
- clientes acima do limite;
- clientes sem limite;
- alertas críticos.

### GET /dashboard/aging

### GET /dashboard/top-exposure

### GET /dashboard/concentration

### GET /dashboard/recent-alerts

## 9. Alertas

### GET /alerts

Filtros:

- status
- severity
- type
- customer_id

### GET /alerts/{id}

### POST /alerts/{id}/acknowledge

### POST /alerts/{id}/resolve

```json
{
  "note": "Limite revisado e aprovado."
}
```

### POST /alerts/{id}/dismiss

## 10. Auditoria

### GET /audit-logs

Filtros:

- user_id
- entity_type
- entity_id
- action
- date_from
- date_to

## 11. Erros

Exemplo:

```json
{
  "data": null,
  "meta": {},
  "errors": [
    {
      "code": "CREDIT_LIMIT_OVERLAP",
      "message": "Já existe um limite ativo no período informado.",
      "field": "valid_from"
    }
  ]
}
```

Códigos HTTP:

- 200: sucesso;
- 201: criado;
- 204: sem conteúdo;
- 400: requisição inválida;
- 401: não autenticado;
- 403: sem permissão;
- 404: não encontrado;
- 409: conflito;
- 422: erro de validação;
- 500: erro interno.
