# Backend Spec 12 — Custos de Produção

## Status
Proposta para implementação incremental.

## Objetivo
Registrar e alocar custos a blocos, ordens, chapas e lotes.

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
Specs 00–11.

## Escopo funcional
Tipos de custo, origem, data, valor, entidade alvo, rateio, histórico e custo por m².

## Regras específicas
DECIMAL; alocação auditável; critérios explícitos; alterações relevantes preservam histórico; custo por m² no backend.

## Contratos e operações
CRUD controlado; allocate; recalculate; detail e relatórios.

## Testes obrigatórios
Soma, rateio, arredondamento, tenant, histórico e serialização.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
