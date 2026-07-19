# Backend Spec 04 — Materiais e Pedreiras

## Status
Proposta para implementação incremental.

## Objetivo
Criar cadastros mestres de materiais e pedreiras.

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
Specs 00–03.

## Escopo funcional
Material com código, nome comercial, categoria, cor e origem; Quarry com código, nome, localização e parceiro opcional.

## Regras específicas
Códigos únicos por organização; referências do mesmo tenant; inativação em vez de remoção quando já utilizados.

## Contratos e operações
CRUD e filtros para materials e quarries.

## Testes obrigatórios
CRUD, unicidade, associações, tenant e capabilities.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
