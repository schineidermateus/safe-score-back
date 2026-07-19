# System Overview

**Projeto:** Stone Platform Backend  
**Tecnologia principal:** Symfony  
**Escopo:** Visão geral da arquitetura do backend.

---

## 1. Objetivo

Este documento apresenta a visão geral do backend da Stone Platform, incluindo seus módulos, responsabilidades, princípios arquiteturais, fluxos principais, integrações, limites de domínio e invariantes.

O backend é responsável por concentrar as regras de negócio, garantir isolamento entre organizações, controlar autorização, manter rastreabilidade, processar importações, calcular custos e preços, registrar auditoria e expor uma API estável para o frontend.

---

## 2. Responsabilidades do Backend

O backend é responsável por:

- autenticação;
- autorização;
- resolução do tenant ativo;
- validação de dados;
- regras de negócio;
- persistência;
- rastreabilidade;
- auditoria;
- importações;
- processamento assíncrono;
- cálculo de custos;
- cálculo e aplicação de preços;
- armazenamento de arquivos;
- observabilidade;
- exposição da API REST;
- integração com serviços externos.

O frontend nunca deve replicar regras de negócio oficiais.

---

## 3. Visão Geral da Arquitetura

```text
Frontend Angular
       │
       ▼
REST API
       │
       ▼
Application Layer
       │
       ▼
Domain Layer
       │
       ▼
Infrastructure Layer
       │
       ├── Database
       ├── Queue
       ├── File Storage
       ├── Mail
       └── External Services
```

A arquitetura deve preservar a separação entre:

- apresentação HTTP;
- aplicação;
- domínio;
- infraestrutura.

---

## 4. Camadas

### 4.1 API / Presentation

Responsável por:

- receber requisições;
- validar formato básico;
- autenticar;
- aplicar autorização;
- converter requests em comandos ou queries;
- serializar respostas;
- retornar erros padronizados.

Não deve conter regras de negócio.

---

### 4.2 Application

Responsável por:

- coordenar casos de uso;
- executar comandos;
- executar queries;
- controlar transações;
- chamar serviços de domínio;
- acionar eventos;
- integrar repositórios;
- coordenar importações e processos assíncronos.

---

### 4.3 Domain

Responsável por:

- entidades;
- value objects;
- invariantes;
- serviços de domínio;
- políticas;
- regras de transição;
- eventos de domínio.

Essa camada não deve depender de Symfony, Doctrine ou infraestrutura externa.

---

### 4.4 Infrastructure

Responsável por:

- persistência com Doctrine;
- repositórios concretos;
- filas;
- armazenamento de arquivos;
- integrações externas;
- logs;
- métricas;
- envio de e-mail;
- implementação de serviços técnicos.

---

## 5. Módulos Principais

A aplicação deve ser organizada por contexto de negócio.

```text
src/
├── Identity/
├── Organization/
├── BusinessPartner/
├── Material/
├── Quarry/
├── Storage/
├── Machine/
├── Block/
├── Production/
├── Slab/
├── Lot/
├── Inventory/
├── Pricing/
├── Costing/
├── Import/
├── Audit/
├── Traceability/
├── FileStorage/
└── Shared/
```

Cada módulo deve possuir fronteiras claras.

---

## 6. Contextos de Domínio

### 6.1 Identity

Responsável por:

- usuários;
- autenticação;
- credenciais;
- sessão;
- recuperação de acesso;
- associação do usuário às organizações.

---

### 6.2 Organization

Responsável por:

- organizações;
- contexto multi-tenant;
- configurações;
- usuários da organização;
- preferências institucionais.

---

### 6.3 Production

Responsável por:

- ordens de produção;
- etapas produtivas;
- máquinas;
- apontamentos;
- consumo de matéria-prima;
- geração de chapas;
- encerramento de ordens.

---

### 6.4 Inventory

Responsável por:

- entradas;
- saídas;
- movimentações;
- localização;
- disponibilidade;
- saldo físico;
- rastreabilidade de estoque.

---

### 6.5 Traceability

Responsável por manter a cadeia de origem e transformação entre:

```text
Pedreira
   │
Bloco
   │
Ordem de Produção
   │
Chapa
   │
Lote
   │
Movimentação
```

---

### 6.6 Costing

Responsável por:

- custos diretos;
- custos indiretos;
- rateios;
- custo por ordem;
- custo por chapa;
- custo por lote;
- composição histórica de custo.

---

### 6.7 Pricing

Responsável por:

- políticas de preço;
- regras comerciais;
- margens;
- tabelas;
- vigência;
- cálculo de preço;
- histórico.

---

### 6.8 Import

Responsável por:

- recebimento de arquivos;
- validação;
- criação de lotes de importação;
- processamento assíncrono;
- erros por linha;
- relatórios;
- histórico.

---

### 6.9 Audit

Responsável por registrar alterações relevantes e ações administrativas.

A auditoria deve ser imutável e separada de logs técnicos.

---

## 7. Fluxo de Requisição

```text
HTTP Request
    │
Authentication
    │
Tenant Resolution
    │
Authorization
    │
Request Mapping
    │
Application Use Case
    │
Domain Rules
    │
Persistence
    │
Domain Events
    │
HTTP Response
```

---

## 8. Fluxo de Comando

Comandos representam operações que alteram estado.

Exemplos:

- criar bloco;
- receber bloco;
- iniciar ordem;
- finalizar produção;
- movimentar lote;
- executar importação;
- aplicar política de preço.

```text
Controller
   │
Command
   │
Handler
   │
Domain
   │
Repository
   │
Transaction
   │
Events
```

---

## 9. Fluxo de Consulta

Queries representam leitura.

Exemplos:

- listar blocos;
- consultar estoque;
- visualizar rastreabilidade;
- consultar custos;
- visualizar auditoria.

```text
Controller
   │
Query
   │
Query Handler
   │
Read Repository
   │
DTO
   │
Response
```

Consultas podem utilizar modelos otimizados sem expor entidades diretamente.

---

## 10. Multi-Tenancy

O sistema é multi-tenant.

O tenant ativo deve ser resolvido a partir do contexto autenticado.

Regras:

- toda entidade de negócio pertence a uma organização;
- toda consulta deve ser filtrada por organização;
- toda gravação deve receber o tenant do contexto;
- o frontend nunca envia `organization_id`;
- nenhuma operação pode misturar tenants;
- caches devem ser isolados;
- filas devem transportar contexto de tenant.

---

## 11. Autorização

A autorização deve ser baseada em capabilities.

Exemplos:

```text
BLOCK_READ
BLOCK_WRITE
PRODUCTION_EXECUTE
IMPORT_EXECUTE
AUDIT_READ
PRICING_MANAGE
```

A API deve validar autorização em toda operação protegida.

O frontend apenas adapta a experiência visual.

---

## 12. Persistência

Doctrine ORM deve ser utilizado para persistência principal.

Princípios:

- entidades não são respostas de API;
- repositórios são definidos por módulo;
- transações são controladas na camada de aplicação;
- consultas complexas podem utilizar DTOs;
- migrations são obrigatórias;
- IDs de domínio são numéricos;
- constraints devem refletir invariantes relevantes.

---

## 13. API

A API deve ser:

- REST;
- versionada;
- tipada por contrato;
- consistente;
- paginada;
- documentada com OpenAPI;
- previsível em erros;
- protegida por autenticação e autorização.

Exemplo:

```text
/api/v1/blocks
/api/v1/production-orders
/api/v1/imports
/api/v1/audit-events
```

---

## 14. Processamento Assíncrono

Processos demorados devem utilizar fila.

Exemplos:

- importações;
- geração de relatórios;
- processamento de arquivos;
- recálculo em lote;
- notificações;
- integrações externas.

Symfony Messenger deve ser utilizado quando adequado.

---

## 15. Eventos

Eventos de domínio representam fatos relevantes.

Exemplos:

- BlockReceived;
- ProductionOrderStarted;
- ProductionOrderCompleted;
- SlabGenerated;
- LotClosed;
- ImportCompleted;
- PricingPolicyApplied.

Eventos podem disparar:

- auditoria;
- atualização de projeções;
- notificações;
- integrações;
- métricas.

---

## 16. Rastreabilidade

Toda transformação relevante deve preservar vínculos de origem.

O sistema deve permitir responder:

- de qual bloco veio esta chapa;
- em qual ordem foi produzida;
- em qual lote foi agrupada;
- onde está armazenada;
- quais custos foram atribuídos;
- qual política de preço foi aplicada.

---

## 17. Auditoria

A auditoria deve registrar:

- usuário;
- organização;
- ação;
- recurso;
- identificador;
- data e hora;
- correlation ID;
- valores relevantes;
- origem da operação.

Auditoria não deve ser apagada por fluxos comuns.

---

## 18. Arquivos

Arquivos devem ser tratados por abstração de storage.

Exemplos:

- planilhas de importação;
- relatórios;
- documentos;
- anexos;
- arquivos de erro.

O domínio não deve depender do provedor físico de armazenamento.

---

## 19. Observabilidade

O backend deve produzir:

- logs estruturados;
- métricas;
- traces;
- correlation ID;
- alertas;
- informações de performance;
- dados de falha de jobs.

Nunca registrar:

- senhas;
- tokens;
- segredos;
- arquivos completos;
- dados sensíveis sem necessidade.

---

## 20. Segurança

Princípios mínimos:

- autenticação stateless;
- senhas com hash seguro;
- validação de entrada;
- autorização obrigatória;
- isolamento por tenant;
- rate limiting quando necessário;
- proteção contra acesso indevido a arquivos;
- gestão segura de secrets;
- dependências atualizadas;
- logs sem informações sensíveis.

---

## 21. Integrações Externas

Integrações devem ser encapsuladas por adapters.

Exemplos:

```text
MailProvider
FileStorageProvider
QueueProvider
TelemetryProvider
ExternalERPProvider
```

O domínio não deve depender diretamente de SDKs externos.

---

## 22. Tratamento de Erros

Erros devem ser classificados.

Categorias:

- validação;
- autenticação;
- autorização;
- não encontrado;
- conflito;
- regra de negócio;
- falha externa;
- erro interno.

Formato recomendado:

```json
{
  "code": "BLOCK_CODE_ALREADY_EXISTS",
  "message": "Já existe um bloco com este código.",
  "details": {},
  "correlation_id": "..."
}
```

---

## 23. Transações

Uma transação deve envolver apenas uma unidade consistente de trabalho.

Evitar:

- transações longas;
- chamadas externas dentro de transação;
- processamento pesado com conexão aberta;
- múltiplos commits no mesmo caso de uso sem justificativa.

---

## 24. Consistência

A consistência forte deve ser utilizada para regras críticas.

Consistência eventual pode ser utilizada para:

- projeções;
- dashboards;
- notificações;
- métricas;
- integrações;
- relatórios.

---

## 25. Estrutura Recomendada

```text
src/
├── Module/
│   ├── Application/
│   │   ├── Command/
│   │   ├── Query/
│   │   ├── DTO/
│   │   └── Service/
│   ├── Domain/
│   │   ├── Entity/
│   │   ├── ValueObject/
│   │   ├── Repository/
│   │   ├── Service/
│   │   ├── Event/
│   │   └── Exception/
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   ├── Messaging/
│   │   ├── Integration/
│   │   └── Storage/
│   └── Presentation/
│       └── Http/
└── Shared/
```

---

## 26. Testes

A estratégia deve incluir:

- testes unitários de domínio;
- testes de handlers;
- testes de integração de repositórios;
- testes de API;
- testes de autorização;
- testes de isolamento multi-tenant;
- testes de importação;
- testes de rastreabilidade;
- testes de migração.

---

## 27. Anti-patterns

Não permitido:

- regras de negócio em controllers;
- entidades Doctrine retornadas diretamente;
- consultas sem filtro de tenant;
- autorização baseada apenas no frontend;
- dependência do domínio em Symfony;
- services genéricos sem contexto;
- transações longas;
- chamadas externas dentro do domínio;
- uso indiscriminado de eventos;
- logs como substituto de auditoria;
- IDs de domínio como strings sem necessidade;
- regras de custo no frontend;
- processamento pesado síncrono.

---

## 28. Critérios de Aceite

A arquitetura está aderente quando:

1. regras de negócio estão no domínio;
2. controllers permanecem finos;
3. casos de uso estão na camada de aplicação;
4. infraestrutura é substituível;
5. toda operação respeita o tenant;
6. toda operação protegida valida capabilities;
7. entidades não são expostas diretamente;
8. processos pesados utilizam fila;
9. rastreabilidade é preservada;
10. auditoria é separada de logs;
11. custos e preços são calculados no backend;
12. contratos da API são documentados;
13. observabilidade está presente;
14. testes cobrem os invariantes principais.

---

## 29. Documentos Relacionados

- `backend-architecture.md`
- `domain-architecture.md`
- `database-architecture.md`
- `multi-tenancy.md`
- `authorization.md`
- `api-architecture.md`
- `import-architecture.md`
- `audit-architecture.md`
- `traceability-architecture.md`
- `costing-pricing-architecture.md`
- `file-storage-architecture.md`
- `observability.md`

---

## 30. Invariantes

As seguintes regras não podem ser violadas:

1. O backend é a fonte de verdade.
2. Toda operação de domínio pertence a um tenant.
3. O frontend nunca define o tenant.
4. Autorização é validada no backend.
5. O domínio não depende de infraestrutura.
6. Entidades não são contratos de API.
7. Auditoria não é substituída por logs.
8. Rastreabilidade deve ser preservada.
9. Custos e preços oficiais são calculados no backend.
10. Processos pesados não bloqueiam requisições HTTP.
11. Toda falha relevante deve ser observável.
12. Nenhum dado de um tenant pode ser acessado por outro.
