# Backend Spec 05 — Localizações e Máquinas

## Status
Proposta para implementação incremental.

## Objetivo
Modelar localizações físicas e recursos produtivos.

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
Specs 00–04.

## Escopo funcional
StorageLocation hierárquica e Machine com tipos operacionais.

## Regras específicas
Hierarquia sem ciclos; códigos únicos por tenant; somente ativos em novos processos; preservar histórico.

## Contratos e operações
CRUD, árvore/listagem e ativação/inativação.

## Testes obrigatórios
Ciclo, hierarquia, tenant, filtros e autorização.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
