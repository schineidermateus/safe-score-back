# Backend Spec 13 — Precificação

## Status
Proposta para implementação incremental.

## Objetivo
Gerar preço sugerido sem substituir a decisão comercial.

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
Specs 00–12.

## Escopo funcional
PricingPolicy, PricingRule, PricingResult, calculatedCost, suggestedPrice e finalPrice.

## Regras específicas
Políticas versionadas; resultado registra versão; finalPrice exige capability; histórico imutável; dinheiro decimal.

## Contratos e operações
CRUD/versionamento de políticas; calculate; set-final-price; history.

## Testes obrigatórios
Versionamento, regra aplicável, arredondamento, tenant e auditoria.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
