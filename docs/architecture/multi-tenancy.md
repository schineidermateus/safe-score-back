# Multi-Tenancy Architecture

> Especificação oficial da arquitetura multi-tenant da Stone Platform.

## 1. Objetivo

Garantir isolamento lógico completo entre organizações (tenants), assegurando que nenhuma operação possa acessar ou modificar dados de outro tenant.

---

## 2. Princípios

- O backend resolve o tenant.
- O frontend nunca informa `organization_id`.
- Todo dado de negócio pertence a exatamente um tenant.
- Todo acesso é filtrado pelo tenant ativo.

---

## 3. Modelo

```text
User
  │
Membership
  │
Organization (Tenant)
  │
Business Data
```

Um usuário pode participar de várias organizações.

---

## 4. Resolução do Tenant

Fluxo:

```text
JWT
 ↓
Usuário autenticado
 ↓
Organização ativa
 ↓
TenantContext
 ↓
Aplicação
```

O `TenantContext` deve estar disponível durante toda a requisição.

---

## 5. Contexto

Criar um serviço único:

```php
interface TenantContext
{
    public function organizationId(): int;
}
```

Nenhum serviço de domínio consulta diretamente a autenticação.

---

## 6. Persistência

Toda entidade de negócio possui:

```text
organization_id
```

Obrigatório, indexado e não nulo.

---

## 7. Consultas

Toda consulta deve ser automaticamente filtrada por tenant.

Preferencialmente utilizar Doctrine Filter ou mecanismo equivalente.

Nunca depender do desenvolvedor lembrar de adicionar o filtro.

---

## 8. Escritas

Ao persistir entidades:

- organization_id é definido pelo backend;
- valores enviados pelo cliente devem ser ignorados.

---

## 9. Filas

Jobs devem transportar:

- organization_id
- correlation_id
- usuário (quando necessário)

O worker deve restaurar o contexto antes de executar a lógica.

---

## 10. Cache

Toda chave deve incluir o tenant.

Exemplo:

```text
tenant:15:block:list
```

---

## 11. Storage

Arquivos devem ser segregados por organização.

Exemplo:

```text
organizations/15/imports/arquivo.xlsx
```

---

## 12. Auditoria

Todo evento de auditoria registra:

- organization_id
- usuário
- recurso
- ação
- timestamp

---

## 13. API

Nenhum endpoint aceita `organization_id` como parâmetro funcional.

O contexto vem da autenticação.

---

## 14. Testes

Cobrir:

- leitura entre tenants;
- escrita entre tenants;
- importações;
- filas;
- cache;
- storage;
- auditoria.

---

## 15. Anti-patterns

Não permitido:

- confiar no organization_id do cliente;
- consultas sem filtro;
- cache compartilhado;
- jobs sem contexto;
- arquivos fora do namespace do tenant.

---

## 16. Checklist

- [ ] TenantContext disponível
- [ ] Filtro automático
- [ ] organization_id obrigatório
- [ ] Cache isolado
- [ ] Storage isolado
- [ ] Jobs propagam contexto

---

## 17. Critérios de Aceite

1. Não existe vazamento entre organizações.
2. Toda operação respeita o TenantContext.
3. Nenhuma API aceita tenant arbitrário.
4. Cache, filas e arquivos são isolados.
5. Testes automatizados validam isolamento.

---

## 18. Invariantes

1. Cada entidade pertence a um único tenant.
2. O backend é responsável pela resolução do tenant.
3. Nenhuma consulta ignora o filtro de tenant.
4. O frontend nunca controla o tenant.
5. O isolamento entre organizações é obrigatório.
