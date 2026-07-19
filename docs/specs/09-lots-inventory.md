# Backend Spec 09 — Lotes e Estoque

## Status
Proposta para implementação incremental.

## Objetivo
Agrupar chapas e controlar estoque por movimentos imutáveis.

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
Specs 00–08.

## Escopo funcional
Lot, LotItem e InventoryMovement com receipt, transfer, reservation, release, shipment, adjustment, disposal e movimentos de produção.

## Regras específicas
Uma chapa em no máximo um lote ativo; movimento não é editado, apenas compensado; ajuste exige capability; idempotência quando necessário.

## Contratos e operações
CRUD de lotes; composição; endpoints de movimentação e histórico.

## Testes obrigatórios
Lotes, transferências, reservas, ajustes, tenant, idempotência e auditoria.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
