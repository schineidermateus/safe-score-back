# Score Model V1

```text
Código: SAFESCORE_INTERNAL_RISK
Versão: 1.0
Escala: 0 a 100
100 = menor risco interno
```

## Pesos

| Componente | Peso |
|---|---:|
| Utilização do limite | 25% |
| Inadimplência atual | 30% |
| Histórico de pagamento | 20% |
| Concentração | 15% |
| Qualidade dos dados | 10% |

## Faixas finais

```text
80–100 LOW
60–79,99 MODERATE
40–59,99 HIGH
0–39,99 CRITICAL
```

## Confiança

```text
HIGH
MEDIUM
LOW
```

A confiança é independente da nota.

## Fórmula

```text
score =
utilization × 0.25
+ delinquency × 0.30
+ history × 0.20
+ concentration × 0.15
+ quality × 0.10
```

Cada componente deve retornar score, peso, valor bruto e reasons.
