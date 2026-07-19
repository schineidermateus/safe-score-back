# Backend Spec 16 — Auditoria e Arquivos

## Status
Proposta para implementação incremental.

## Objetivo
Consolidar auditoria e suporte a fotos/documentos.

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
Specs 00–15.

## Escopo funcional
AuditLog; fotos de blocos, chapas e defeitos; documentos de apoio; metadados e download autorizado.

## Regras específicas
Isolamento por tenant; MIME real; limite; nomes não previsíveis; entityId inteiro; logs sem segredos.

## Contratos e operações
Upload, list, download e delete controlado; consultas de auditoria.

## Testes obrigatórios
Upload inválido, acesso cruzado, auditoria, exclusão e autorização.

## Critérios de aceite
- Implementação restrita ao escopo desta spec.
- Build, lint/análise estática e testes passam.
- Documentação e contratos correspondem ao código real.
- Sem vazamento entre tenants.
- IDs permanecem inteiros em banco, PHP/TypeScript e JSON.
