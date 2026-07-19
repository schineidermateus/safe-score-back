# ADR 001 — Autenticação externa

## Status

Aceita.

## Contexto

O frontend autentica o usuário em um provedor externo. O Stone Traceability continua responsável pelo vínculo do usuário local, status, memberships, organização atual, capabilities, isolamento multi-tenant e auditoria.

## Decisão

- O backend aceita somente access token JWT externo RS256 pelo header `Authorization: Bearer`.
- O tipo esperado do token, issuer, audience e JWKS são configurações do servidor.
- A identidade externa é `(issuer, subject)` e precisa possuir vínculo local ativo.
- Email, `user_id`, roles, grupos e scopes externos não identificam o usuário nem concedem capabilities locais.
- O backend não recebe senha, não persiste password hash e não possui chave privada de autenticação.
- `organization_id`, quando presente no token externo, é apenas uma indicação e sempre é revalidado contra organização e membership locais.
- Sem indicação de tenant, uma única membership ativa é resolvida automaticamente. Múltiplas memberships retornam `ORGANIZATION_SELECTION_REQUIRED` até que a Spec 01 defina um contrato explícito de seleção.
- Tokens JWT são validados localmente e não possuem revogação imediata no provedor enquanto não houver introspecção. Bloqueios locais continuam efetivos na próxima revalidação.

## Consequências

- Não existe `POST /auth/login` local.
- O backend não emite JWT para troca de organização.
- O provedor precisa publicar JWKS HTTPS e emitir access token com `typ`, `iss`, `aud`, `sub`, `iat` e `exp` compatíveis.
- A indisponibilidade do JWKS é uma falha externa observável e não uma credencial inválida.
