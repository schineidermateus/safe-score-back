# Domain Architecture

> Documento normativo para implementação do domínio da Stone Platform.
>
> **Objetivo:** fornecer uma especificação clara, consistente e facilmente consumível por desenvolvedores e ferramentas de IA (como Codex), priorizando estrutura, regras explícitas e exemplos.

---

# 1. Objetivos

O domínio representa as regras de negócio da plataforma.

Todo comportamento crítico deve existir nesta camada.

O domínio **não depende** de:

- Symfony
- Doctrine
- HTTP
- Banco de dados
- Filas
- Storage
- Frameworks

---

# 2. Princípios

- Backend é a fonte de verdade.
- O domínio é puro.
- Toda regra possui um único lugar.
- Toda alteração de estado passa por métodos do domínio.
- Nenhuma entidade pública possui setters arbitrários.

---

# 3. Estrutura

```text
Module/
├── Domain/
│   ├── Aggregate/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Event/
│   ├── Policy/
│   ├── Service/
│   ├── Specification/
│   ├── Factory/
│   ├── Repository/
│   └── Exception/
```

Cada módulo possui seu próprio domínio.

---

# 4. Bounded Contexts

Cada contexto possui linguagem própria.

Exemplos:

- Identity
- Organization
- Production
- Inventory
- Costing
- Pricing
- Import
- Audit
- Traceability

Evitar compartilhamento desnecessário de modelos.

---

# 5. Ubiquitous Language

Os nomes usados no código devem refletir exatamente os termos do negócio.

Exemplo:

- Block
- Slab
- Lot
- Quarry
- ProductionOrder

Evitar nomes genéricos como `DataService`, `Manager`, `Helper`.

---

# 6. Aggregate Roots

Somente Aggregate Roots podem ser carregadas diretamente pelos repositórios.

Exemplos:

- Block
- ProductionOrder
- Lot

Objetivos:

- proteger invariantes;
- controlar alterações;
- publicar eventos.

---

# 7. Entidades

Características:

- identidade própria;
- ciclo de vida;
- igualdade por identidade.

Nunca utilizar entidades como DTOs.

---

# 8. Value Objects

Características:

- imutáveis;
- comparados por valor;
- sem identidade.

Exemplos:

- Money
- Dimensions
- Weight
- Email
- DocumentNumber

---

# 9. Domain Services

Utilizar apenas quando uma regra não pertence naturalmente a uma entidade.

Exemplos:

- CostCalculationService
- PricingPolicyService

Não utilizar Services como repositório de lógica arbitrária.

---

# 10. Policies

Policies representam decisões do domínio.

Exemplo:

```text
CanCloseLotPolicy
```

Retornam decisão baseada em regras de negócio.

---

# 11. Specifications

Utilizadas para regras reutilizáveis.

Exemplos:

- BlockIsAvailable
- LotHasInventory
- ProductionOrderCanFinish

---

# 12. Factories

Factories encapsulam construções complexas.

Nunca utilizar factories para lógica de persistência.

---

# 13. Repositories

Interfaces pertencem ao domínio.

Implementações pertencem à infraestrutura.

---

# 14. Domain Events

Eventos representam fatos consumados.

Exemplos:

- BlockReceived
- SlabGenerated
- LotClosed
- PricingApplied

Eventos nunca substituem regras de negócio.

---

# 15. Invariantes

Toda Aggregate Root protege suas invariantes.

Exemplo:

```text
Lot
 ├── não pode fechar vazio
 ├── não aceita movimentação após fechamento
 └── deve pertencer a um tenant
```

---

# 16. Multi-tenancy

Toda entidade de negócio pertence exatamente a uma organização.

Jamais permitir mistura entre tenants.

---

# 17. Transações

Uma transação corresponde a uma unidade consistente de trabalho.

Eventos externos devem ocorrer após commit.

---

# 18. Concorrência

Operações críticas devem considerar concorrência.

Preferir optimistic locking quando aplicável.

---

# 19. Idempotência

Operações repetidas devem produzir resultado consistente sempre que possível.

Importações e integrações devem possuir mecanismos idempotentes.

---

# 20. Estados

Entidades com ciclo de vida devem possuir estados explícitos.

Exemplo:

```text
Draft
 ↓
Running
 ↓
Completed
 ↓
Archived
```

Evitar flags booleanas para representar máquinas de estado.

---

# 21. Dependências Permitidas

```text
Presentation
      ↓
Application
      ↓
Domain
      ↑
Infrastructure
```

O domínio não referencia camadas superiores.

---

# 22. Convenções para IA

Para facilitar ferramentas como Codex:

- classes pequenas;
- responsabilidades únicas;
- nomes explícitos;
- métodos curtos;
- invariantes documentadas;
- eventos nomeados no passado;
- interfaces separadas das implementações;
- comentários apenas quando agregam contexto.

---

# 23. Anti-patterns

Não permitido:

- setters públicos indiscriminados;
- lógica de negócio em controllers;
- entidades Doctrine como resposta da API;
- objetos anêmicos;
- helpers genéricos;
- dependência do domínio em Symfony;
- acesso direto ao banco pelo domínio.

---

# 24. Checklist

- [ ] Aggregate Root definida
- [ ] Invariantes protegidas
- [ ] Value Objects utilizados
- [ ] Eventos publicados quando necessário
- [ ] Interfaces de repositório no domínio
- [ ] Nenhuma dependência de infraestrutura
- [ ] Regras testáveis

---

# 25. Critérios de Aceite

A arquitetura de domínio está correta quando:

1. Toda regra crítica reside no domínio.
2. O domínio é independente de framework.
3. Agregados preservam consistência.
4. Eventos representam fatos, não comandos.
5. Multi-tenancy é respeitado.
6. O código é facilmente navegável por humanos e ferramentas de IA.

---

# 26. Invariantes Globais

1. O domínio nunca depende do Symfony.
2. Nenhuma entidade altera estado por setters públicos.
3. Toda Aggregate Root protege suas invariantes.
4. Todo objeto de negócio pertence a um tenant.
5. Toda regra de negócio importante possui testes.
