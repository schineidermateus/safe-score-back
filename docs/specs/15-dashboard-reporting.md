# Backend Spec 15 — Dashboard e Consultas Gerenciais

## Status
Proposta para implementação incremental.

## Objetivo
Fornecer agregações industriais para o frontend.

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
Specs 00–14.

## Escopo funcional
Summary, production, yield, inventory, costs e aging, com filtros de período e cadastros.

## Regras específicas
Agregações no backend; tenant; data de referência; evitar N+1; cache curto somente se seguro.

## Contratos e operações
GET /dashboard/summary, /production, /yield, /inventory e /costs.

## Testes obrigatórios
Filtros, tenant, consistência, vazio e performance.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
