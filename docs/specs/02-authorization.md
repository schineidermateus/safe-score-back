# Backend Spec 02 — Roles e Capabilities

## Status
Proposta para implementação incremental.

## Objetivo
Definir autorização industrial baseada em capabilities.

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
Catálogo central, roles como agrupadores, voters/authorization service, perfis padrão e auditoria administrativa.

## Regras específicas
Código não verifica nomes de role; backend é autoridade; capability futura não cria endpoint inexistente.

## Contratos e operações
Capabilities para parceiros, cadastros, blocos, ordens, chapas, lotes, estoque, rastreabilidade, rendimento, custos, pricing, imports e auditoria.

## Testes obrigatórios
Acesso permitido/negado, tenant, alteração administrativa e ausência de role checks.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
