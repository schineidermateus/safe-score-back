# Multi-tenancy

O produto usa banco MySQL compartilhado e isolamento por `organization_id`. A organização é extraída do JWT autenticado e validada contra uma membership ativa; payloads comuns nunca aceitam `organization_id`.

Toda entidade de negócio possui tenant direto ou uma relação obrigatória cujo tenant é verificável. Repositories recebem `Organization`, queries filtram o tenant e associações cross-tenant são rejeitadas. Uma busca por ID de outro tenant responde como recurso inexistente.

Imports usam o tenant do contexto tanto no banco quanto no storage físico (`var/imports/{organizationId}`). Auditoria registra organização e actor. Chaves de cache de negócio devem incorporar o ID da organização e ser invalidadas quando o contexto muda.
