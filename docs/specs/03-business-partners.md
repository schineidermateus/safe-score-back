# Backend Spec 03 — Parceiros de Negócio

## Status
Proposta para implementação incremental.

## Objetivo
Criar cadastro unificado de parceiros sem conceitos de crédito.

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
CRUD, filtros, paginação, tipos CUSTOMER, SUPPLIER, SERVICE_PROVIDER, QUARRY, TRANSPORTER e OTHER.

## Regras específicas
Parceiro pertence ao tenant; documento único conforme regra; status ativo/inativo; sem score, limite, exposição ou inadimplência.

## Contratos e operações
Endpoints REST de listagem, detalhe, criação, edição e ativação/inativação.

## Testes obrigatórios
Tenant, unicidade, filtros, autorização, status e IDs inteiros.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
