# Receivable Rules

## Entidade mínima

```text
id INT AUTO_INCREMENT
organization_id INT
customer_id INT
source
external_id
document_number
issue_date
due_date
original_amount DECIMAL(19,2)
open_amount DECIMAL(19,2)
paid_amount DECIMAL(19,2)
payment_date DATE NULL
status
created_at
updated_at
```

## Status

```text
OPEN
PARTIALLY_PAID
PAID
OVERDUE
CANCELLED
```

## Regras

- Valores não podem ser negativos.
- `open_amount <= original_amount`.
- Títulos pagos e cancelados permanecem no histórico.
- Pagamento no prazo: `payment_date <= due_date`.
- Entram na exposição: OPEN, PARTIALLY_PAID e OVERDUE.
- Idempotência preferencial: `UNIQUE (organization_id, source, external_id)`.
