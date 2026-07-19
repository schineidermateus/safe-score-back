# Backend Spec 11 — Rendimento e Perdas

## Status
Proposta para implementação incremental.

## Objetivo
Calcular indicadores da transformação de bloco em chapas.

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
Specs 00–10.

## Escopo funcional
Volume, área bruta, área útil, rejeitos, quantidade, rendimento e perda por bloco, material e máquina.

## Regras específicas
Fórmulas documentadas; precisão e arredondamento centralizados; zero division seguro; snapshots quando necessário.

## Contratos e operações
Endpoints de cálculo, detalhe e agregações filtráveis.

## Testes obrigatórios
Fórmulas, arredondamento, rejeitos, reprocessamento e tenant.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
