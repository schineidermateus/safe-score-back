# SafeScore — Coding Guidelines

## 1. Princípios gerais

- Código deve refletir o domínio financeiro.
- Regras de negócio não devem ficar em controllers ou componentes.
- Preferir soluções simples e explícitas.
- Evitar abstrações antes de existir repetição real.
- Toda alteração financeira relevante deve ser auditável.
- Toda consulta deve respeitar o tenant.

## 2. Arquitetura backend

Stack sugerida:

- PHP 8.4+
- Symfony
- Doctrine ORM
- MySQL
- PHPUnit
- PHPStan
- PHP-CS-Fixer

Estrutura por módulo:

```text
src/
  Customers/
    Domain/
    Application/
    Infrastructure/
    Presentation/
  Credit/
  Receivables/
  Imports/
  Alerts/
  Reporting/
```

### Domain
Entidades, objetos de valor, regras e interfaces.

### Application
Casos de uso, commands, queries e DTOs.

### Infrastructure
Doctrine, filas, armazenamento e integrações.

### Presentation
Controllers, request DTOs e serialização.

## 3. Controllers

Controllers devem:

- receber a requisição;
- validar o DTO;
- chamar um caso de uso;
- transformar a resposta.

Controllers não devem:

- calcular exposição;
- decidir severidade;
- consultar repositórios diretamente sem necessidade;
- conter regras financeiras.

## 4. Valores monetários

- Usar decimal ou objeto Money.
- Nunca usar float.
- Serializar valores monetários como string.
- Definir arredondamento explicitamente.

## 5. Multi-tenancy

- organization_id vem do contexto autenticado.
- Nunca confiar em organization_id enviado pelo cliente.
- Repositórios devem exigir contexto da organização.
- Criar testes de isolamento entre tenants.

## 6. Frontend Angular

Stack sugerida:

- Angular Standalone Components
- Angular Material
- RxJS
- Signals quando adequado
- SASS
- ApexCharts

Estrutura:

```text
src/app/
  core/
  shared/
  layout/
  features/
    dashboard/
    customers/
    credit-limits/
    receivables/
    imports/
    alerts/
```

### Core
Autenticação, interceptors, guards e configuração global.

### Shared
Componentes genéricos e reutilizáveis.

### Features
Funcionalidades de negócio.

## 7. Componentes

- Preferir componentes pequenos.
- Smart components coordenam dados.
- Presentational components recebem inputs e emitem eventos.
- Não colocar regras financeiras em templates.
- Estados de loading, erro e vazio são obrigatórios.

## 8. Tipagem

- Evitar `any`.
- Usar interfaces ou types para contratos.
- Separar DTO de API de view model quando necessário.
- Enums devem ser compartilhados apenas quando realmente estáveis.

## 9. Nomenclatura

Backend:

- Classes em PascalCase.
- Métodos e propriedades em camelCase.
- Casos de uso com verbo: `CreateCustomer`, `SetCreditLimit`.
- Queries: `GetCustomerFinancialSummary`.
- Eventos: `CreditLimitChanged`.

Frontend:

- Arquivos em kebab-case.
- Componentes com sufixo `.component`.
- Serviços com sufixo `.service`.
- Stores com sufixo `.store`.

## 10. Testes

Prioridades:

1. Regras financeiras.
2. Isolamento multiempresa.
3. Importação e idempotência.
4. Permissões.
5. Fluxos críticos.

Nomear testes descrevendo comportamento:

```text
it_should_generate_a_critical_alert_when_exposure_exceeds_limit
```

## 11. Git

- `main` sempre estável.
- Branches pequenas.
- Commits objetivos.
- Pull requests mesmo em equipe pequena.
- Não misturar refatoração ampla com funcionalidade.
- Registrar decisões arquiteturais em ADRs.

## 12. Codex

Ao usar Codex:

- fornecer contexto do módulo;
- informar arquivos permitidos;
- descrever critérios de aceitação;
- pedir testes;
- impedir alterações fora do escopo;
- revisar toda regra financeira;
- não aceitar dependências novas sem justificativa.

Exemplo de prompt:

```text
Implemente o caso de uso SetCreditLimit no módulo Credit.
Respeite a arquitetura existente.
Não altere outros módulos.
Impeça sobreposição de períodos ativos.
Adicione testes unitários e de integração.
Valores monetários não podem usar float.
```

## 13. Definition of Done

Uma tarefa só está concluída quando:

- atende aos critérios de aceitação;
- possui testes relevantes;
- respeita tenant e permissões;
- possui tratamento de erro;
- possui estado de loading/erro/vazio no frontend;
- não introduz warning de análise estática;
- documentação foi atualizada quando necessário.

## 14. Identificadores persistidos

- Usar INT UNSIGNED AUTO_INCREMENT para User, Organization, OrganizationMembership e Customer.
- O banco gera o identificador.
- Não usar UUID ou ULID nessas entidades.
- Toda consulta de negócio continua explicitamente limitada pela organização atual.
