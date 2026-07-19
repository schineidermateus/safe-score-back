# Backend Spec 14 — Importações Industriais

## Status
Proposta para implementação incremental.

## Objetivo
Adaptar a infraestrutura genérica de importação.

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
Specs 00–13 conforme cada tipo.

## Escopo funcional
Tipos para parceiros, materiais, pedreiras, localizações, blocos, chapas, lotes, abertura de estoque e custos.

## Regras específicas
Pipeline upload→mapping→validation→preview→processing→result; tipo não implementado é rejeitado; tenant, idempotência e erros por linha.

## Contratos e operações
Endpoints existentes adaptados por tipo, sem importadores financeiros.

## Testes obrigatórios
Preview, erros, retry, duplicidade, arquivos inválidos, tenant e autorização.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
