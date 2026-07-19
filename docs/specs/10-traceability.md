# Backend Spec 10 — Rastreabilidade

## Status
Proposta para implementação incremental.

## Objetivo
Fornecer consulta bidirecional completa.

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
Specs 00–09.

## Escopo funcional
Block → ProductionOrder → Slab → Lot → InventoryMovement → StorageLocation e caminho inverso.

## Regras específicas
Usar relações reais; tenant-aware; timeline por eventos e timestamps; paginação quando necessário.

## Contratos e operações
Busca por código; rastreabilidade por bloco; por chapa; timeline.

## Testes obrigatórios
Integridade da cadeia, dados incompletos, tenant e performance básica.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
