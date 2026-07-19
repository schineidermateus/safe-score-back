# Backend Spec 01 — Estado da infraestrutura externa

## Escopo

A fundação autentica exclusivamente access tokens emitidos por um provedor externo. O backend não recebe senha, não persiste password hash e não emite token de autenticação.

## Fluxo disponível

| Método | Caminho | Responsabilidade |
|---|---|---|
| GET | `/auth/me` | retorna perfil, organização e membership do contexto validado |
| GET | `/organizations` | lista organizações ativas acessíveis ao usuário externo vinculado |

`GET /api/v1/me` permanece como alias. Login local e seleção que emitia JWT próprio foram removidos. O contrato de seleção de organização para usuários com múltiplas memberships permanece como decisão explícita da Spec 01.

## Segurança

- somente JWT RS256 com tipo configurado é aceito;
- assinatura, `kid`, issuer, audience, `iat`, `nbf` e `exp` são validados;
- a chave pública vem de JWKS HTTPS configurado no servidor;
- a identidade é resolvida somente por `(issuer, subject)` em `external_identity` ativa;
- `user_id`, email, roles e scopes externos não concedem vínculo ou capability;
- usuário, organização e membership continuam sendo autoridades locais;
- falhas de token usam resposta sanitizada e correlation ID;
- indisponibilidade do JWKS retorna falha de serviço, não credencial inválida.

## Banco

A baseline cria `external_identity` com PK `BIGINT UNSIGNED`, FK para `app_user`, status local e unicidade binária de `(issuer, subject)`. `Version20260719050000` contém somente índices de consulta de status e memberships. Não existe coluna `password_hash`.

## Configuração

São obrigatórios `JWKS_URI`, `JWT_ISSUER`, `JWT_AUDIENCE` e `JWT_TOKEN_TYPE`. Cache, refresh e clock skew são configuráveis. Nenhuma chave privada é necessária.

## Limitação local

O hostname MySQL `database` não é resolvido nesta máquina. Mapping, análise estática e testes independentes de banco são executáveis; migrations, fixtures e sincronização real continuam pendentes de MySQL.
