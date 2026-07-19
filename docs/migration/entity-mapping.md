# Entity mapping da migration

| SafeScore | Plataforma industrial | Resultado |
|---|---|---|
| User | User | preservado |
| Organization | Organization | preservado |
| OrganizationMembership | OrganizationMembership | preservado; capabilities associadas por perfis persistidos |
| Customer | BusinessPartner futuro | removido da baseline; depende da Spec 03 |
| CreditLimit | — | removido |
| Receivable/ReceivablePayment | — | removidos |
| FinancialIndicator/Score | — | removidos |
| ImportBatch/ImportRow | ImportBatch/ImportRow | infraestrutura preservada; importadores financeiros removidos |
| AuditLog | AuditLog | preservado com correlation ID opcional |

Entidades fundacionais preservadas ou consolidadas: `User`, `Organization`, `OrganizationMembership`, `Role`, `Capability`, `ImportBatch`, `ImportRow` e `AuditLog`. Todos os IDs e foreign keys usam `BIGINT UNSIGNED` e são gerados pelo MySQL.
