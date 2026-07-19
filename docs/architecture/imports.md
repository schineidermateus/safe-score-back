# Imports

A infraestrutura genérica preserva upload CSV, inspeção por stream, mapping, validação, preview, processamento, erros por linha, hash e auditoria. Batches e queries são tenant-scoped, e arquivos ficam fora de `public` em diretórios separados pelo ID inteiro da organização.

Os tipos planejados são `BUSINESS_PARTNERS`, `MATERIALS`, `QUARRIES`, `STORAGE_LOCATIONS`, `BLOCKS`, `SLABS`, `LOTS`, `INVENTORY_OPENING` e `PRODUCTION_COSTS`. Nesta baseline nenhum possui validator/processor final; tentativas retornam `IMPORT_TYPE_NOT_IMPLEMENTED` e não armazenam arquivo.

Cada importer futuro deve definir schema, idempotência, transação, validação de associações no tenant, auditoria e testes antes de ser marcado como implementado.
