# Frontend Migration — SafeScore para Plataforma de Rastreabilidade de Rochas

## 1. Objetivo

Este documento define a migração do frontend Angular do SafeScore para uma plataforma B2B de rastreabilidade, rendimento, custos e precificação de rochas ornamentais.

A migração preserva os padrões arquiteturais existentes e substitui progressivamente o domínio de crédito pelo domínio industrial. Não se trata de uma reescrita completa.

## 2. Princípios preservados

- Angular com Standalone Components.
- `ChangeDetectionStrategy.OnPush`.
- Signals e RxJS.
- Estado isolado por feature.
- Services HTTP e adapters tipados.
- Lazy loading.
- Autorização por capabilities.
- Multi-tenancy resolvido pelo backend.
- O frontend nunca envia `organization_id`.
- IDs numéricos.
- Dinheiro recebido e enviado como string decimal.
- Nenhum cálculo industrial ou financeiro duplicado no frontend.
- Loading, empty state e error state explícitos.
- Testes de adapters, services, stores e componentes críticos.
- Manutenção do `frontend-style-guide.md` e `coding-guidelines.md`.

## 3. Mapeamento de features

| SafeScore | Plataforma de rochas | Estratégia |
|---|---|---|
| Identity | Identity | Manter |
| Organizations | Organizations | Manter |
| Customers | Business Partners | Adaptar |
| Credit Limits | Sem equivalente | Remover |
| Receivables | Fora do MVP | Remover |
| Financial Indicators | Yield e Production Costs | Substituir |
| Score | Pricing futuro | Remover inicialmente |
| Imports | Imports | Adaptar |
| Dashboard | Industrial Dashboard | Substituir |
| Audit | Audit | Manter |
| Core/Shared | Core/Shared | Manter e revisar |

## 4. Estrutura alvo

```text
features/
  identity/
  organizations/
  business-partners/
  materials/
  quarries/
  storage-locations/
  machines/
  blocks/
  production-orders/
  slabs/
  lots/
  inventory/
  traceability/
  yield/
  production-costs/
  pricing/
  imports/
  dashboard/
  audit/
```

## 5. Estratégia

A migração deve ocorrer verticalmente, por feature. Cada etapa deve tratar:

1. modelos;
2. DTOs HTTP;
3. adapters;
4. API service;
5. estado;
6. páginas;
7. componentes;
8. rotas;
9. capabilities;
10. testes;
11. remoção do código antigo relacionado.

Não executar renomeações globais indiscriminadas.

## 6. Fases

### Fase 0 — Preparação

- Criar o fork.
- Atualizar nome, descrição e branding provisório.
- Mapear rotas, menus, mocks e arquivos ligados ao SafeScore.
- Criar inventário de código deprecated.
- Congelar novas funcionalidades financeiras no fork.

### Fase 1 — Neutralização do domínio antigo

Remover do fluxo principal:

```text
/customers
/credit-limits
/receivables
/financial-indicators
/score
```

Remover textos, cards, menus, ícones e mocks ligados a crédito.

O código pode permanecer temporariamente somente quando houver dependências técnicas ainda não migradas.

### Fase 2 — Núcleo

Validar:

- autenticação;
- troca de organização;
- guards;
- interceptors;
- autorização por capability;
- layout;
- tratamento global de erros;
- componentes comuns;
- paginação;
- filtros;
- upload;
- auditoria.

### Fase 3 — Business Partners

Migrar `customers` para `business-partners`, sem herdar conceitos de score, limite ou inadimplência.

Tipos iniciais:

```text
CUSTOMER
SUPPLIER
SERVICE_PROVIDER
QUARRY
TRANSPORTER
OTHER
```

### Fase 4 — Cadastros industriais

Criar:

```text
materials
quarries
storage-locations
machines
```

### Fase 5 — Blocks

Fluxos mínimos:

- listagem;
- filtros;
- cadastro;
- edição;
- detalhes;
- medidas;
- material;
- fornecedor;
- pedreira;
- localização;
- status;
- fotos;
- QR Code;
- histórico.

Volume e custos consolidados devem vir do backend.

### Fase 6 — Production Orders

Fluxos mínimos:

- criar;
- agendar;
- iniciar;
- concluir;
- cancelar;
- selecionar bloco e máquina;
- informar espessura planejada;
- consultar resultado da produção.

Mudanças de status devem usar ações explícitas.

### Fase 7 — Slabs

- vínculo com bloco e ordem;
- medidas;
- espessura;
- área bruta;
- área útil;
- classificação;
- defeitos;
- fotos;
- localização;
- lote;
- histórico.

O frontend apenas exibe os cálculos fornecidos pela API.

### Fase 8 — Lots e Inventory

- criação de lotes;
- inclusão e remoção de chapas;
- transferências;
- reservas;
- saídas;
- ajustes;
- descarte;
- inventário;
- histórico.

Movimentações não devem ser tratadas como edição direta de localização.

### Fase 9 — Traceability

Consultas:

```text
Block → Production Order → Slab → Lot → Inventory Movement → Location
Slab → Production Order → Block → Material → Quarry → Supplier
```

A feature não deve criar uma segunda fonte de verdade.

### Fase 10 — Yield e Production Costs

Exibir dados calculados pelo backend:

- área bruta;
- área útil;
- área rejeitada;
- rendimento;
- perda;
- custo total;
- custo por m²;
- custos por etapa.

### Fase 11 — Pricing

Separar claramente:

```text
calculatedCost
suggestedPrice
finalPrice
```

A política aplicada e sua versão devem ser exibidas quando disponíveis.

### Fase 12 — Dashboard

Substituir o dashboard financeiro por métricas industriais:

- blocos em estoque;
- blocos em processamento;
- ordens abertas;
- chapas produzidas;
- área útil;
- rendimento;
- perda;
- custo por m²;
- valor de estoque;
- estoque parado.

O frontend não deve carregar listas extensas para calcular agregações.

## 7. Capabilities iniciais

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

O frontend controla visibilidade e habilitação; o backend continua sendo a autoridade.

## 8. Contratos

- Não espalhar DTOs HTTP nos componentes.
- Utilizar adapters.
- Manter dinheiro como string decimal.
- Manter IDs numéricos.
- Padronizar datas e timezone.
- Não enviar `organization_id`.
- Não converter dinheiro para `number` para realizar cálculos.
- Não reproduzir fórmulas de volume, área, rendimento, custo ou preço.

## 9. Estado e cache

- Estado em memória por feature.
- Limpeza no logout.
- Limpeza ou separação na troca de organização.
- Nenhum dado entre tenants.
- Evitar `localStorage` para dados industriais e financeiros.
- Evitar `shareReplay` sem invalidação.
- Invalidar listas e detalhes após mutações.
- Evitar cache por componente e múltiplas fontes de verdade.

## 10. Imports

Reaproveitar o fluxo:

```text
upload → mapping → validation → preview → processing → result
```

Novos tipos:

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

## 11. Menu sugerido

```text
Dashboard

Operação
- Blocos
- Ordens de produção
- Chapas
- Lotes
- Estoque
- Rastreabilidade

Cadastros
- Parceiros
- Materiais
- Pedreiras
- Localizações
- Máquinas

Gestão
- Rendimento
- Custos
- Precificação
- Importações
- Auditoria

Administração
- Organização
- Usuários
- Perfis e permissões
```

## 12. Testes

Cobrir:

- adapters;
- services;
- stores;
- guards;
- troca de organização;
- limpeza de estado;
- loading, error e empty state;
- transições de status;
- movimentações;
- valores monetários;
- rastreabilidade;
- componentes críticos.

## 13. Ordem de implementação

```text
1. Preparação
2. Neutralização do SafeScore
3. Núcleo
4. Business Partners
5. Materials
6. Quarries
7. Storage Locations
8. Machines
9. Blocks
10. Production Orders
11. Slabs
12. Lots
13. Inventory
14. Traceability
15. Yield
16. Production Costs
17. Pricing
18. Imports
19. Dashboard
20. Remoção final do legado
```

## 14. Critérios de conclusão

- Nenhuma rota principal depende do domínio de crédito.
- Menus, textos e mocks antigos foram removidos.
- Features industriais estão integradas à API.
- Capabilities estão aplicadas.
- A troca de tenant não reutiliza estado indevido.
- Não há cálculos de domínio no frontend.
- Build, lint e testes passam.
- Código deprecated foi removido.
- A documentação corresponde à implementação.

## 15. Fora de escopo

- ERP completo;
- contas a pagar e receber;
- fiscal;
- contabilidade;
- integração com teares;
- RFID;
- visão computacional;
- IA;
- otimização de corte;
- operação offline completa;
- marketplace;
- logística de exportação.
