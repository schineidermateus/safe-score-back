# Backend Spec 07 — Ordens de Produção

## Status
Proposta para implementação incremental.

## Objetivo
Controlar o processamento de blocos.

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
Specs 00–06.

## Escopo funcional
Ordem com código, bloco, máquina, espessura, datas, status e observações.

## Regras específicas
Estados DRAFT, SCHEDULED, IN_PROGRESS, COMPLETED e CANCELLED; start/complete/cancel explícitos; transação; evitar ordens ativas incompatíveis.

## Contratos e operações
CRUD em draft; schedule; start; complete; cancel; detail e filtros.

## Testes obrigatórios
Transições, tenant, concorrência lógica, idempotência e auditoria.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
