# Backend Migration — SafeScore para Plataforma de Rastreabilidade de Rochas

> Estado de implementação: a baseline SafeScore foi substituída por uma baseline modular para banco novo. O domínio financeiro não faz parte do schema atual. A Spec 00 contém somente identidade, organizações, authorization técnica persistida, imports genéricos e auditoria. Entidades industriais dependem de suas próprias specs. Tipos de import industrial permanecem desabilitados até essas implementações.

## 1. Objetivo

Este documento define a migração do backend Symfony do SafeScore para uma plataforma B2B multi-tenant de rastreabilidade, rendimento, custos e precificação de rochas ornamentais.

A infraestrutura, os padrões arquiteturais, a segurança, a autorização, a auditoria e o mecanismo de importação devem ser preservados sempre que forem compatíveis.

## 2. Princípios preservados

- Symfony, Doctrine ORM e MySQL.
- APIs REST.
- Serviços de aplicação.
- Regras de domínio fora de controllers.
- DTOs de entrada e saída.
- Repositories orientados ao domínio.
- Autorização por capabilities.
- Backend como fonte de verdade.
- Tenant resolvido no backend.
- Frontend nunca envia `organization_id`.
- IDs numéricos.
- DECIMAL na persistência e string decimal na API.
- Tratamento uniforme de erros.
- Migrations versionadas.
- Auditoria.
- Testes unitários, de integração e API.
- Padrões do `coding-guidelines.md`.

## 3. Mapeamento do domínio

| SafeScore | Destino | Estratégia |
|---|---|---|
| User | User | Manter |
| Organization | Organization | Manter |
| Membership | Membership | Manter |
| Role | Role | Manter |
| Capability | Capability | Adaptar |
| Customer | BusinessPartner | Adaptar ou substituir |
| CreditLimit | Sem equivalente | Remover |
| Receivable | Fora do MVP | Remover |
| FinancialIndicator | Yield/Cost projections | Substituir |
| Score | Pricing futuro | Não migrar diretamente |
| ImportBatch | ImportBatch | Manter |
| ImportRow | ImportRow | Manter |
| AuditLog | AuditLog | Manter |

## 4. Domínio alvo

```text
User
Organization
OrganizationMembership
Role
Capability

BusinessPartner
Material
Quarry
StorageLocation
Machine

Block
BlockMeasurement
BlockPhoto

ProductionOrder
Slab
SlabClassification
Defect

Lot
LotItem
InventoryMovement

ProductionCost
CostAllocation
YieldSnapshot

PricingPolicy
PricingRule
PricingResult

ImportBatch
ImportRow
AuditLog
```

## 5. Banco de dados

### Estratégia recomendada

Como o fork será um novo produto e não há requisito informado de preservação de dados reais do SafeScore, recomenda-se criar uma nova baseline.

Preservar apenas estruturas realmente reutilizáveis:

```text
users
organizations
organization_memberships
roles
capabilities
role_capabilities
membership_roles
import_batches
import_rows
audit_logs
```

Não carregar no novo baseline:

```text
customers
credit_limits
receivables
financial_indicators
scores
score_histories
```

Evitar renomear tabelas sem equivalência real de domínio.

## 6. Migração vertical

Cada módulo deve conter:

1. regras de negócio;
2. entidades e value objects;
3. enums;
4. repositories;
5. serviços de aplicação;
6. autorização;
7. DTOs;
8. controllers;
9. migrations;
10. testes;
11. auditoria;
12. documentação.

## 7. Fases

### Fase 0 — Preparação

- Criar o fork e a branch de migração.
- Mapear entidades, tabelas, endpoints, services e repositories antigos.
- Escolher nova baseline.
- Congelar features financeiras no fork.
- Criar inventário do código deprecated.

### Fase 1 — Núcleo

Validar:

- autenticação;
- tenant resolver;
- current organization;
- authorization;
- capabilities;
- auditoria;
- tratamento de erros;
- serialização;
- migrations;
- isolamento multi-tenant.

### Fase 2 — Capabilities industriais

```text
BUSINESS_PARTNER_READ
BUSINESS_PARTNER_WRITE
MATERIAL_READ
MATERIAL_WRITE
QUARRY_READ
QUARRY_WRITE
STORAGE_LOCATION_READ
STORAGE_LOCATION_WRITE
BLOCK_READ
BLOCK_WRITE
BLOCK_RECEIVE
BLOCK_MOVE
PRODUCTION_ORDER_READ
PRODUCTION_ORDER_WRITE
PRODUCTION_ORDER_START
PRODUCTION_ORDER_COMPLETE
PRODUCTION_ORDER_CANCEL
SLAB_READ
SLAB_WRITE
SLAB_CLASSIFY
SLAB_MOVE
LOT_READ
LOT_WRITE
INVENTORY_READ
INVENTORY_MOVE
INVENTORY_ADJUST
TRACEABILITY_READ
YIELD_READ
PRODUCTION_COST_READ
PRODUCTION_COST_WRITE
PRICING_READ
PRICING_WRITE
IMPORT_READ
IMPORT_WRITE
AUDIT_READ
```

Não usar verificações diretas de roles.

### Fase 3 — Business Partners

Criar ou migrar para `BusinessPartner`.

Tipos:

```text
CUSTOMER
SUPPLIER
SERVICE_PROVIDER
QUARRY
TRANSPORTER
OTHER
```

Regras:

- vínculo obrigatório com tenant;
- documentos únicos dentro do escopo definido;
- status ativo/inativo;
- nenhuma referência a score ou limite;
- associações sempre validadas pelo tenant.

### Fase 4 — Cadastros industriais

Criar:

```text
Material
Quarry
StorageLocation
Machine
```

### Fase 5 — Blocks

Campos iniciais possíveis:

```text
id
organization
code
originCode
material
supplier
quarry
storageLocation
length
width
height
volumeM3
grossWeight
netWeight
purchaseCost
freightCost
initialTotalCost
status
receivedAt
notes
createdAt
updatedAt
```

Regras:

- código único por organização;
- associações do mesmo tenant;
- volume calculado no backend;
- status alterado por ações de domínio;
- movimentações auditáveis;
- valores monetários como DECIMAL.

### Fase 6 — Production Orders

Estados possíveis:

```text
DRAFT
SCHEDULED
IN_PROGRESS
COMPLETED
CANCELLED
```

Regras:

- transições validadas;
- bloco e máquina pertencem ao tenant;
- iniciar, concluir e cancelar são ações explícitas;
- não aceitar PATCH genérico de status;
- conclusão registra resultados de produção.

### Fase 7 — Slabs

Campos iniciais:

```text
id
organization
productionOrder
block
code
sequenceNumber
length
width
thickness
grossAreaM2
usableAreaM2
qualityGrade
status
storageLocation
lot
createdAt
updatedAt
```

Regras:

- rastreabilidade obrigatória até o bloco;
- área calculada no backend;
- classificação auditável;
- movimentação cria `InventoryMovement`;
- relações sempre do mesmo tenant.

### Fase 8 — Lots e Inventory

Criar:

```text
Lot
LotItem
InventoryMovement
```

Tipos de movimentação:

```text
RECEIPT
TRANSFER
RESERVATION
RELEASE
SHIPMENT
ADJUSTMENT
DISPOSAL
PRODUCTION_INPUT
PRODUCTION_OUTPUT
```

Regras:

- movimentações não são editadas diretamente;
- correções usam compensação;
- ajustes exigem capability específica;
- origem e destino são validados;
- idempotência quando aplicável;
- nenhuma operação cruza tenants.

### Fase 9 — Traceability

Consultas mínimas:

```text
Block → ProductionOrder → Slab → Lot → InventoryMovement → StorageLocation
Slab → ProductionOrder → Block → Material → Quarry → Supplier
```

Não duplicar dados apenas para montar a trilha.

### Fase 10 — Yield

O backend calcula:

- volume;
- área bruta;
- área útil;
- área rejeitada;
- rendimento;
- perda;
- quantidade de chapas;
- custo por área bruta;
- custo por área útil.

Fórmulas devem ser documentadas e testadas.

### Fase 11 — Production Costs

Tipos iniciais:

```text
BLOCK_ACQUISITION
FREIGHT
CUTTING
LABOR
ENERGY
DIAMOND_WIRE
RESIN
POLISHING
HANDLING
PACKAGING
WASTE_DISPOSAL
OTHER
```

Regras:

- valor em DECIMAL;
- origem do custo obrigatória;
- alocação auditável;
- histórico quando afetar custo ou preço;
- arredondamento documentado.

### Fase 12 — Pricing

Separar:

```text
calculatedCost
suggestedPrice
finalPrice
```

Regras:

- custo e preço sugerido calculados no backend;
- preço sugerido registra política e versão;
- preço final exige autorização;
- mudanças de política não alteram históricos silenciosamente;
- nenhuma fórmula universal fixa para todas as empresas.

### Fase 13 — Imports

Tipos:

```text
BUSINESS_PARTNERS
MATERIALS
QUARRIES
STORAGE_LOCATIONS
BLOCKS
SLABS
LOTS
INVENTORY_OPENING
PRODUCTION_COSTS
```

Preservar upload, mapping, validação, preview, processamento, erros, idempotência e isolamento multi-tenant.

### Fase 14 — Dashboard

Criar endpoints agregados, evitando cálculo pesado no frontend:

```text
GET /dashboard/summary
GET /dashboard/production
GET /dashboard/yield
GET /dashboard/inventory
GET /dashboard/costs
```

## 8. Multi-tenancy

- Toda entidade de negócio pertence direta ou indiretamente ao tenant.
- Toda consulta filtra pelo tenant.
- Toda criação usa o tenant autenticado.
- Toda associação valida tenant.
- IDs de outro tenant não devem revelar existência.
- Imports, fotos, cache e arquivos devem ser isolados.
- Chaves de cache devem conter o tenant.
- Troca de organização invalida contexto e cache.

## 9. APIs

Diretrizes:

- recursos e ações de domínio;
- paginação e filtros padronizados;
- IDs numéricos;
- dinheiro como string decimal;
- datas padronizadas;
- erros uniformes;
- sem `organization_id` nos payloads comuns;
- idempotency key em operações críticas.

Ações preferidas:

```text
POST /blocks/{id}/receive
POST /blocks/{id}/move
POST /production-orders/{id}/start
POST /production-orders/{id}/complete
POST /production-orders/{id}/cancel
POST /slabs/{id}/classify
POST /inventory/movements
```

## 10. Auditoria

Auditar:

- criação e alteração de bloco;
- recebimento e movimentação;
- início, conclusão e cancelamento de ordem;
- criação e classificação de chapa;
- lotes;
- ajustes de estoque;
- custos;
- políticas e resultados de preço;
- imports;
- ações administrativas.

Registrar:

```text
organization
user
action
entityType
entityId
timestamp
relevantChanges
correlationId
```

## 11. Medidas e dinheiro

- Nunca usar float para dinheiro.
- Persistir dinheiro em DECIMAL.
- Retornar dinheiro como string.
- Definir unidade canônica para comprimento, peso, área e volume.
- Não manter campos de medida sem unidade documentada.
- Documentar escala de percentuais para evitar ambiguidade entre `0.15` e `15`.

## 12. Arquivos e fotos

- Isolamento por tenant.
- Autorização no download.
- MIME real validado.
- Limites de tamanho.
- Nomes internos não previsíveis.
- Metadados persistidos.
- Remoção controlada.
- URLs temporárias quando aplicável.

## 13. Cache

- Tenant sempre presente na chave.
- Invalidação após mutações.
- Nenhum cache de autorização sem expiração.
- Cuidado com estoque, custos e rastreabilidade.
- Agregações podem usar cache curto.
- Logout e troca de organização invalidam contexto.

## 14. Remoção do legado

Remover progressivamente:

```text
Credit Limits
Receivables
Financial Indicators
Credit Score
Score History
Credit Recommendations
```

Ordem:

1. mapear dependências;
2. remover endpoints;
3. remover services;
4. remover repositories;
5. remover entidades;
6. remover do baseline;
7. remover fixtures;
8. remover capabilities;
9. remover testes;
10. remover documentação;
11. verificar referências restantes.

## 15. Testes

Cobrir:

- isolamento multi-tenant;
- authorization;
- transições de status;
- movimentações;
- idempotência;
- imports;
- volume;
- área;
- rendimento;
- perda;
- custos;
- precificação;
- auditoria;
- serialização monetária;
- filtros;
- paginação;
- arquivos;
- endpoints agregados.

## 16. Ordem de implementação

```text
1. Preparação
2. Nova baseline
3. Núcleo
4. Capabilities
5. Business Partners
6. Materials
7. Quarries
8. Storage Locations
9. Machines
10. Blocks
11. Production Orders
12. Slabs
13. Lots
14. Inventory
15. Traceability
16. Yield
17. Production Costs
18. Pricing
19. Imports
20. Dashboard
21. Remoção final do legado
```

## 17. Critérios de conclusão

- APIs antigas não estão expostas.
- O baseline não contém tabelas financeiras antigas.
- Capabilities industriais estão aplicadas.
- Todas as entidades estão isoladas por tenant.
- O frontend não envia `organization_id`.
- Cálculos industriais estão no backend.
- Valores monetários usam DECIMAL/string.
- Movimentações e alterações críticas são auditáveis.
- Imports industriais funcionam.
- Testes, migrations e fixtures refletem o novo produto.
- Código deprecated foi removido.
- A documentação corresponde ao sistema real.

## 18. Fora de escopo

- ERP completo;
- financeiro completo;
- fiscal e contabilidade;
- transmissão fiscal;
- integração automática com máquinas;
- RFID;
- visão computacional;
- IA;
- otimização de corte;
- microserviços;
- event sourcing completo;
- operação offline;
- marketplace;
- logística completa de exportação.
