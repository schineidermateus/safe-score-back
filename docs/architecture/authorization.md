# Authorization

Autenticação identifica o usuário; autorização avalia capabilities efetivas na membership da organização atual.

`OrganizationMembership.role` mantém somente a classificação administrativa usada por invariantes como a proteção do último `OWNER`. Ela não concede acesso. Perfis persistidos em `roles` agrupam `capabilities` por meio de `role_capabilities` e são atribuídos pela tabela `membership_roles`.

Toda operação de negócio chama `AuthorizationService::assertGranted()` com uma capability. Controllers não comparam nomes de roles. Apenas memberships ativas concedem acesso.

O catálogo contém capabilities industriais, imports, auditoria e as operações administrativas `MANAGE_MEMBERS` e `ASSIGN_OWNER`. Perfis padrão são dados configuráveis; adicionar uma capability futura não a concede automaticamente a nenhum nome de role.
