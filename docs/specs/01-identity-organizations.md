# Backend Spec 01 — Identidade e Organizações

## Status
Infraestrutura externa preparada; fluxo funcional de seleção pendente.

## Objetivo
Consolidar autenticação, usuários, organizações e memberships.

## Regras globais
- IDs inteiros numéricos; não usar UUID ou ULID.
- O backend resolve a organização pelo contexto autenticado.
- O frontend não envia `organization_id` em operações comuns.
- Toda entidade e consulta de negócio respeita isolamento multi-tenant.
- Valores monetários usam DECIMAL no banco e string decimal na API.
- Cálculos e regras de domínio permanecem no backend.
- Autorização por capabilities, nunca por nome de role.
- Operações críticas devem ser auditáveis.
- Não antecipar funcionalidades fora desta spec.

## Dependências
Specs anteriores na ordem numérica.

## Escopo funcional
Autenticação externa, perfil, organizações acessíveis, seleção de organização atual, status de usuário, identidade externa e membership.

## Regras específicas
Usuário bloqueado não autentica; membership inativa não concede acesso; organização atual nunca vem de payload de domínio.

## Contratos e operações

- `GET /auth/me`: retorna usuário, organização e membership do contexto autenticado.
- `GET /organizations`: lista somente organizações ativas ligadas ao usuário por memberships ativas.

Não existe endpoint de login local. O contrato de seleção para usuários com múltiplas organizações será definido antes de implementar a etapa funcional da Spec 01; até lá, o backend não emite token próprio nem mantém seleção de tenant fictícia.

O contrato legado `GET /api/v1/me` permanece como alias compatível.

## Decisões de identidade

- O provedor externo autentica a identidade; o backend não recebe nem persiste senha.
- O backend valida access tokens externos RS256 com a chave pública disponível no `JWKS_URI` e não possui chave privada de autenticação.
- A identidade local é resolvida exclusivamente por `issuer + subject`; `user_id`, email e roles do token não concedem vínculo ou autorização.
- `ExternalIdentity` distingue vínculo externo, usuário local e status do vínculo.
- Usuário, organização e membership são relidos do banco em cada request protegida. A desativação posterior à emissão invalida o acesso sem depender da expiração do token.
- Nomes de roles não concedem acesso. Roles persistidas agrupam capabilities.

## Seleção inicial

- nenhuma membership ativa: contexto organizacional negado;
- uma membership ativa: seleção automática não ambígua;
- várias memberships ativas: `ORGANIZATION_SELECTION_REQUIRED` até a definição do contrato explícito;
- organização ausente, inativa, alheia ou ligada por membership inativa: resposta de recurso indisponível sem revelar sua existência.

## Testes obrigatórios
Token externo, assinatura, claims, vínculo externo, status local, membership, tenant, capabilities e IDs numéricos.

Revogação no provedor depende da expiração do access token enquanto não houver introspecção. Suspensão de usuário, identidade externa, membership ou organização local produz efeito na próxima requisição que revalida esse contexto.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
