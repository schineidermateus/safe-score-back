# Financial Indicators

Todos os cálculos recebem `reference_date`.

## Exposição

```text
sum(open_amount) dos recebíveis elegíveis
```

## Crédito disponível

```text
active_limit - exposure
```

Sem limite ativo, o resultado é indisponível, não zero.

## Utilização

```text
exposure / active_limit × 100
```

## Vencido

```text
sum(open_amount) onde due_date < reference_date
```

## Maior atraso

```text
max(reference_date - due_date)
```

## Pontualidade

```text
títulos pagos no prazo / títulos pagos × 100
```

## Concentração

```text
exposição do cliente / exposição total da organização × 100
```

## Qualidade dos dados

Considerar limite ativo, documento, quantidade de títulos, histórico pago e atualização dos dados.

Nunca usar float.
