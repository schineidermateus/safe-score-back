# Credit Limit Rules

## Entidade

```text
id INT AUTO_INCREMENT
organization_id INT
customer_id INT
amount DECIMAL(19,2)
valid_from DATE
valid_until DATE NULL
status
reason
approved_by_user_id INT
created_at
updated_at
```

## Status

```text
DRAFT
ACTIVE
EXPIRED
REVOKED
```

## Regras

- Um cliente pode possuir histórico de limites.
- Não pode haver sobreposição de períodos ativos.
- `amount > 0`.
- `valid_until` não pode ser anterior a `valid_from`.
- Limite vigente depende da data de referência.
- Revogação não apaga histórico.
- Alterações devem gerar auditoria.
- Valores monetários nunca usam float.
