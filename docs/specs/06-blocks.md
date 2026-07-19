# Backend Spec 06 — Blocos

## Status
Proposta para implementação incremental.

## Objetivo
Implementar o agregado Block.

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
Specs 00–05.

## Escopo funcional
Cadastro, recebimento, medidas, material, fornecedor, pedreira, localização, custos iniciais, status, movimentação e timeline.

## Regras específicas
Código único; medidas positivas; volume calculado no backend; status por ações explícitas; movimentação auditada; dinheiro DECIMAL.

## Contratos e operações
CRUD de dados gerais; receive, move, reserve, release e detail.

## Testes obrigatórios
Volume, transições, tenant, dinheiro, movimentação, auditoria e autorização.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
