# Backend Architecture

**Projeto:** Stone Platform Backend  
**Tecnologia:** Symfony 7.3, PHP 8.4, Doctrine ORM, MySQL 8+

> Este documento define a arquitetura oficial do backend da Stone Platform e serve como referência para todas as decisões técnicas do projeto.

---

# 1. Objetivo

Definir a arquitetura do backend, seus princípios, camadas, responsabilidades, padrões, integrações e invariantes, garantindo uma base escalável, modular e testável.

---

# 2. Objetivos Arquiteturais

- Centralizar todas as regras de negócio.
- Isolar domínios por contexto.
- Facilitar evolução independente dos módulos.
- Garantir multi-tenancy.
- Preservar rastreabilidade completa.
- Manter alta testabilidade.
- Reduzir acoplamento entre infraestrutura e domínio.

---

# 3. Princípios

1. Backend é a fonte de verdade.
2. Arquitetura modular.
3. Domínio independente de framework.
4. Controllers finos.
5. Casos de uso explícitos.
6. Dependências apontam para dentro.
7. Infraestrutura é substituível.
8. Toda regra importante é testável.

---

# 4. Estilo Arquitetural

A solução adota uma combinação de:

- Arquitetura em Camadas;
- Clean Architecture;
- DDD (Domain-Driven Design);
- CQRS leve (Commands e Queries separados);
- Event-Driven para eventos de domínio;
- Modular Monolith.

---

# 5. Fluxo Geral

```text
HTTP Request
    │
Controller
    │
Command / Query
    │
Application
    │
Domain
    │
Repository
    │
Doctrine
    │
MySQL
```

---

# 6. Camadas

## Presentation

Responsável por HTTP, autenticação, serialização e validação superficial.

## Application

Orquestra casos de uso, transações e eventos.

## Domain

Contém entidades, agregados, value objects, serviços de domínio e regras.

## Infrastructure

Implementa persistência, filas, storage, integrações externas e observabilidade.

---

# 7. Organização Recomendada

```text
src/
├── Identity/
├── Organization/
├── Block/
├── Production/
├── Inventory/
├── Pricing/
├── Costing/
├── Import/
├── Audit/
├── Traceability/
├── FileStorage/
└── Shared/
```

Cada módulo deve conter:

```text
Application/
Domain/
Infrastructure/
Presentation/
```

---

# 8. Controllers

Controllers devem apenas:

- receber requisições;
- validar formato;
- delegar para Commands/Queries;
- retornar respostas.

Nunca implementar regras de negócio.

---

# 9. Application Layer

Responsabilidades:

- Commands;
- Queries;
- Handlers;
- DTOs;
- coordenação de transações;
- disparo de eventos;
- integração entre módulos.

---

# 10. Domain Layer

Contém:

- Entities;
- Value Objects;
- Domain Services;
- Policies;
- Specifications;
- Domain Events;
- Exceptions.

Não depende de Symfony nem Doctrine.

---

# 11. Infrastructure

Implementa:

- Doctrine;
- Messenger;
- Storage;
- Cache;
- Mail;
- Telemetria;
- Providers externos.

---

# 12. Persistência

- Doctrine ORM;
- Migrations obrigatórias;
- IDs numéricos;
- Repositórios por módulo;
- Entidades nunca expostas diretamente pela API.

---

# 13. CQRS

Commands alteram estado.

Queries realizam leitura otimizada.

Não é necessário banco separado de leitura.

---

# 14. Eventos

Eventos representam fatos consumidos por:

- auditoria;
- notificações;
- métricas;
- integrações;
- projeções.

---

# 15. Multi-tenancy

Todo acesso deve ocorrer dentro do tenant resolvido pelo backend.

Nunca confiar em `organization_id` enviado pelo cliente.

---

# 16. Segurança

- JWT;
- Capabilities;
- autenticação delegada ao provedor externo, sem senha no backend;
- Validação de entrada;
- Secrets fora do código.

---

# 17. Processamento Assíncrono

Symfony Messenger deve ser utilizado para:

- importações;
- relatórios;
- integrações;
- notificações;
- tarefas demoradas.

---

# 18. API

Características:

- REST;
- versionada;
- OpenAPI;
- DTOs;
- paginação;
- erros padronizados.

---

# 19. Observabilidade

Implementar:

- logs estruturados;
- métricas;
- tracing;
- Correlation ID;
- monitoramento de filas.

---

# 20. Testes

Cobertura recomendada:

- domínio;
- handlers;
- repositórios;
- API;
- autorização;
- multi-tenancy;
- importações.

---

# 21. Padrões Obrigatórios

- Dependency Injection;
- Repository Pattern;
- Value Objects;
- Domain Events;
- DTOs;
- Factories quando apropriado.

---

# 22. Anti-patterns

Não permitido:

- lógica em controllers;
- domínio dependente de Symfony;
- SQL em controllers;
- entidades retornadas pela API;
- serviços "God Class";
- regras de negócio no frontend.

---

# 23. Checklist

- [ ] Controller fino
- [ ] Caso de uso explícito
- [ ] Regra no domínio
- [ ] DTOs separados
- [ ] Evento quando necessário
- [ ] Testes
- [ ] Observabilidade
- [ ] Multi-tenancy respeitado

---

# 24. Critérios de Aceite

1. Domínio independente.
2. Infraestrutura substituível.
3. Controllers sem regras de negócio.
4. Casos de uso centralizados.
5. API consistente.
6. Testes automatizados.
7. Auditoria e rastreabilidade preservadas.
8. Processos assíncronos desacoplados.

---

# 25. Documentos Relacionados

- system-overview.md
- domain-architecture.md
- database-architecture.md
- multi-tenancy.md
- authorization.md
- api-architecture.md
- import-architecture.md
- audit-architecture.md
- traceability-architecture.md
- costing-pricing-architecture.md
- file-storage-architecture.md
- observability.md

---

# 26. Invariantes

1. O domínio é independente do framework.
2. O backend é a única fonte de verdade.
3. Toda operação pertence a um tenant.
4. Autorização sempre validada no backend.
5. Controllers permanecem finos.
6. Eventos não substituem regras de negócio.
7. Infraestrutura nunca dita regras de domínio.
8. Toda mudança relevante é testável e observável.
