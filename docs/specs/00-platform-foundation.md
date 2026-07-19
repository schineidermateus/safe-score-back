# Backend Spec 00 — Fundação da Plataforma

## Status
Proposta para implementação incremental.

## Objetivo
Criar a nova baseline limpa do backend após a remoção do domínio SafeScore.

## Regras globais
- IDs inteiros numéricos com PKs `BIGINT UNSIGNED AUTO_INCREMENT`; não usar UUID ou ULID.
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
Remover migrations, entidades, endpoints, fixtures e testes financeiros; preservar identidade, organizações, memberships, roles, capabilities, imports e auditoria; recriar o banco do zero.

## Regras específicas
O banco alvo é novo; não criar migrations de transformação ou cópia. Validar schema a partir de banco vazio.

## Contratos e operações
Nova baseline de migrations; fixtures técnicas; capabilities necessárias aos recursos preservados; remoção dos endpoints antigos.

## Testes obrigatórios
Banco vazio, migrations, schema validate, fixtures, autenticação, tenant e ausência de UUID/ULID.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
