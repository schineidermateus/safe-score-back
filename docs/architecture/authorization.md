# Authorization

Autenticação identifica o usuário. Autorização define o que ele pode fazer na organização atual.

A role pertence ao vínculo `OrganizationMembership`.

## Roles

```text
OWNER
ADMIN
ANALYST
VIEWER
```

## Matriz inicial

| Ação | OWNER | ADMIN | ANALYST | VIEWER |
|---|---:|---:|---:|---:|
| Consultar dados | Sim | Sim | Sim | Sim |
| Alterar clientes | Sim | Sim | Sim | Não |
| Alterar limites | Sim | Sim | Sim | Não |
| Alterar recebíveis | Sim | Sim | Sim | Não |
| Importar | Sim | Sim | Sim | Não |
| Resolver alertas | Sim | Sim | Sim | Não |
| Recalcular score | Sim | Sim | Sim | Não |
| Gerenciar membros | Sim | Sim | Não | Não |
| Atribuir OWNER | Sim | Não | Não | Não |

## Regras

- Apenas memberships `ACTIVE` concedem acesso.
- A organização deve manter ao menos um OWNER ativo.
- O último OWNER não pode ser removido ou rebaixado.
- Regras devem ficar centralizadas em `AuthorizationService`, policies ou capabilities.
- Não espalhar comparações de string de role pelos controllers.
