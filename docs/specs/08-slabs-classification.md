# Backend Spec 08 — Chapas, Classificação e Defeitos

## Status
Proposta para implementação incremental.

## Objetivo
Registrar chapas com rastreabilidade até bloco e ordem.

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
Specs 00–07.

## Escopo funcional
Chapa, medidas, áreas, espessura, qualidade, defeitos, status, localização e histórico.

## Regras específicas
Área bruta calculada; área útil não supera bruta; relações do mesmo tenant; classificação auditada; movimentação gera evento de estoque.

## Contratos e operações
CRUD permitido; classify; defects; move; list/detail.

## Testes obrigatórios
Área, rastreabilidade, classificação, defeitos, tenant e movimentação.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
