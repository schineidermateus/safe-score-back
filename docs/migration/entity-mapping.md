# Entity mapping da migration

| SafeScore | Plataforma industrial | Resultado |
|---|---|---|
| User | User | preservado |
| Organization | Organization | preservado |
| OrganizationMembership | OrganizationMembership | preservado; capabilities associadas por perfis persistidos |
| Customer | BusinessPartner | substituído por entidade nova |
| CreditLimit | — | removido |
| Receivable/ReceivablePayment | — | removidos |
| FinancialIndicator/Score | — | removidos |
| ImportBatch/ImportRow | ImportBatch/ImportRow | infraestrutura preservada; importadores financeiros removidos |
| AuditLog | AuditLog | preservado com correlation ID opcional |

Entidades fundacionais novas: `Role`, `Capability`, `BusinessPartner`, `Material`, `Quarry`, `StorageLocation` e `Machine`. Todos os IDs e foreign keys são inteiros unsigned gerados pelo MySQL.
