# Database Architecture

> Arquitetura oficial da camada de persistência da Stone Platform.

**Stack**
- MySQL 8+
- Doctrine ORM
- Doctrine Migrations

---

# 1. Objetivos

A camada de banco deve:

- preservar consistência;
- suportar multi-tenancy;
- escalar para grandes volumes;
- facilitar auditoria;
- manter rastreabilidade.

---

# 2. Princípios

- Banco não contém regras de negócio.
- Integridade é reforçada por constraints.
- O domínio continua sendo a fonte das regras.
- Toda mudança estrutural ocorre por migration.

---

# 3. Organização

```text
Application
    │
Doctrine Repository
    │
Doctrine ORM
    │
MySQL
```

---

# 4. Convenções

## Tabelas

- snake_case
- singular
- sem prefixo global; nomes devem refletir explicitamente o módulo ou agregado

Exemplo:

```text
app_user
organization_membership
import_batches
```

## Colunas

- snake_case
- nomes explícitos

---

# 5. Chaves Primárias

Todas as entidades utilizam:

- BIGINT UNSIGNED AUTO_INCREMENT

Exemplo:

```sql
id BIGINT UNSIGNED PRIMARY KEY
```

IDs públicos não devem carregar significado de negócio.

---

# 6. Multi-tenancy

Toda tabela de negócio deve possuir:

```text
organization_id
```

Regras:

- obrigatório;
- indexado;
- FK para organização;
- usado em todas as consultas.

---

# 7. Chaves Estrangeiras

Sempre que possível:

- utilizar FK;
- ON DELETE RESTRICT por padrão;
- CASCADE apenas quando fizer sentido.

---

# 8. Índices

Obrigatórios para:

- organization_id
- created_at
- foreign keys
- colunas frequentemente filtradas

Criar índices compostos quando necessário.

Exemplo:

```text
(organization_id, status)
```

---

# 9. Datas

Todas as tabelas devem possuir:

```text
created_at
updated_at
```

Opcional:

```text
deleted_at
```

---

# 10. Soft Delete

Utilizar apenas quando houver necessidade funcional.

Jamais substituir auditoria.

---

# 11. Doctrine

Cada Aggregate Root possui sua Entity.

Evitar heranças complexas.

Preferir composição.

---

# 12. Repositórios

Interfaces:

```text
Domain/
```

Implementações:

```text
Infrastructure/Persistence
```

---

# 13. Migrations

Toda alteração estrutural exige migration.

Nunca editar migrations já executadas em produção.

---

# 14. Integridade

Preferir:

- UNIQUE
- CHECK
- FK
- NOT NULL

Sempre que representarem regras permanentes.

---

# 15. Performance

Evitar:

- SELECT *
- N+1
- consultas sem índice
- joins desnecessários

Utilizar paginação.

---

# 16. Transações

Responsabilidade da camada Application.

Não abrir transações em controllers.

---

# 17. Arquivos

Banco armazena apenas metadados.

Arquivos permanecem em storage.

---

# 18. Auditoria

Auditoria utiliza tabelas próprias.

Nunca sobrescrever histórico.

---

# 19. Rastreabilidade

Relacionamentos devem permitir navegar:

```text
Quarry
 ↓
Block
 ↓
Production Order
 ↓
Slab
 ↓
Lot
```

---

# 20. Backup

Recomenda-se:

- backups automáticos;
- restore testado;
- retenção definida;
- criptografia.

---

# 21. Anti-patterns

Não permitido:

- regras de negócio em triggers;
- SQL em controllers;
- ausência de índices;
- consultas sem tenant;
- migrations manuais.

---

# 22. Checklist

- [ ] Migration criada
- [ ] Índices revisados
- [ ] FK definidas
- [ ] organization_id presente
- [ ] Convenções respeitadas
- [ ] Performance avaliada

---

# 23. Critérios de Aceite

1. Todas as tabelas seguem convenções.
2. Multi-tenancy é obrigatório.
3. Integridade referencial preservada.
4. Performance adequada.
5. Estrutura reproduzível via migrations.
6. Banco permanece independente das regras de negócio.

---

# 24. Documentos Relacionados

- backend-architecture.md
- domain-architecture.md
- multi-tenancy.md
- traceability-architecture.md
- audit-architecture.md

---

# 25. Invariantes

1. Toda entidade pertence a um tenant.
2. Toda alteração estrutural passa por migration.
3. Banco não substitui regras de domínio.
4. Dados críticos preservam integridade referencial.
5. Nenhum tenant pode acessar dados de outro.
