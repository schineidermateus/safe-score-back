# SafeScore — Domain Model

## 1. Estratégia

O MVP deve usar um monólito modular com separação lógica por domínio.

Módulos:

- Identity
- Organizations
- Customers
- Credit
- Receivables
- Imports
- Alerts
- Reporting
- Audit

## 2. Entidades principais

### Organization

Representa a empresa assinante.

Campos:

- id
- legal_name
- trade_name
- document
- segment
- currency
- timezone
- status
- created_at
- updated_at

### User

Representa uma pessoa autenticável.

Campos:

- id
- name
- email
- password_hash
- status
- email_verified_at
- created_at
- updated_at

### OrganizationUser

Relaciona usuário e organização.

Campos:

- id
- organization_id
- user_id
- role
- status
- created_at

Roles:

- ADMIN
- MANAGER
- ANALYST
- VIEWER

### Customer

Representa o cliente B2B analisado pela organização.

Campos:

- id
- organization_id
- external_id
- legal_name
- trade_name
- document
- segment
- status
- account_manager
- created_at
- updated_at
- deleted_at

### CreditLimit

Mantém histórico de limites.

Campos:

- id
- organization_id
- customer_id
- amount
- currency
- valid_from
- valid_until
- status
- approved_by_user_id
- reason
- created_at

Status:

- DRAFT
- ACTIVE
- EXPIRED
- REVOKED

### Receivable

Representa um título a receber.

Campos:

- id
- organization_id
- customer_id
- source
- external_id
- document_number
- issue_date
- due_date
- original_amount
- open_amount
- paid_amount
- payment_date
- status
- imported_at
- created_at
- updated_at

### ImportBatch

Representa uma importação.

Campos:

- id
- organization_id
- type
- file_name
- status
- total_rows
- success_rows
- error_rows
- started_at
- completed_at
- created_by_user_id

Tipos:

- CUSTOMERS
- CREDIT_LIMITS
- RECEIVABLES

Status:

- PENDING
- VALIDATING
- READY
- PROCESSING
- COMPLETED
- COMPLETED_WITH_ERRORS
- FAILED

### ImportRow

Representa uma linha processada.

Campos:

- id
- import_batch_id
- row_number
- raw_data
- normalized_data
- status
- errors
- entity_id

### Alert

Campos:

- id
- organization_id
- customer_id
- type
- severity
- status
- title
- message
- fingerprint
- detected_at
- acknowledged_at
- resolved_at
- resolved_by_user_id
- resolution_note

### AuditLog

Campos:

- id
- organization_id
- user_id
- action
- entity_type
- entity_id
- before_data
- after_data
- metadata
- created_at

## 3. Objetos de valor

### Money
- amount
- currency

### DocumentNumber
CPF ou CNPJ normalizado e validado.

### DateRange
- start
- end opcional

### Percentage
Valor decimal entre 0 e 100.

## 4. Serviços de domínio

### ExposureCalculator
Calcula a exposição de um cliente.

### CreditAvailabilityCalculator
Calcula crédito disponível e utilização.

### AgingClassifier
Classifica recebíveis por faixa.

### PortfolioConcentrationCalculator
Calcula concentração por cliente.

### AlertEvaluator
Avalia regras e cria, atualiza ou resolve alertas.

### ImportDeduplicationService
Detecta registros já importados.

## 5. Agregados sugeridos

### CustomerCreditProfile
Raiz: Customer

Relaciona:

- limite vigente;
- histórico de limites;
- exposição calculada;
- alertas;
- indicadores.

### ImportBatch
Raiz: ImportBatch

Controla o ciclo da importação e suas linhas.

## 6. Relacionamentos

```text
Organization 1 ── N OrganizationUser N ── 1 User
Organization 1 ── N Customer
Customer 1 ── N CreditLimit
Customer 1 ── N Receivable
Customer 1 ── N Alert
Organization 1 ── N ImportBatch
ImportBatch 1 ── N ImportRow
Organization 1 ── N AuditLog
```

## 7. Restrições

- Customer.document deve ser único por organização quando informado.
- Receivable(source, external_id) deve ser único por organização quando external_id existir.
- Não permitir sobreposição de limites ativos.
- Toda entidade de negócio deve possuir organization_id.
- Exclusões devem preferir soft delete quando houver impacto histórico.
