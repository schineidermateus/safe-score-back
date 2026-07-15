# Multi-Tenancy

O SafeScore utiliza um único banco MySQL, tabelas compartilhadas e isolamento por `organization_id`.

## Estrutura

```text
User
  └── OrganizationMembership
        └── Organization
              ├── Customer
              ├── CreditLimit
              ├── Receivable
              ├── ImportBatch
              ├── Alert
              ├── CustomerScore
              └── CustomerScoreSnapshot
```

## Regras obrigatórias

- Toda entidade de negócio pertence a uma organização.
- `organization_id` vem do contexto atual, nunca do payload.
- Repositórios devem exigir organização explicitamente.
- Todos os IDs são `INT AUTO_INCREMENT`.
- IDs sequenciais não são mecanismo de segurança.
- Consultas entre tenants devem retornar 403 ou 404 sem vazar existência.
- Unicidades de negócio devem incluir `organization_id`.

## Contexto temporário

Até o JWT existir:

```text
CurrentUserProviderInterface
CurrentOrganizationProviderInterface
CurrentMembershipProviderInterface
```

Implementações de desenvolvimento podem usar:

```env
DEV_USER_ID=1
DEV_ORGANIZATION_ID=1
```

Esses providers não podem funcionar em produção.

## Testes obrigatórios

- Organização A não lista, lê ou altera dados da B.
- O mesmo documento pode existir em organizações diferentes.
- O mesmo documento não pode duplicar na mesma organização.
- `organization_id` enviado pelo cliente é ignorado ou rejeitado.
