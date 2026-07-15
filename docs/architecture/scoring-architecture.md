# Scoring Architecture

O score é um indicador interno, explicável e versionado. Não é score de bureau, previsão estatística de default ou garantia de pagamento.

## Fluxo

```text
Customer + CreditLimit + Receivable + Portfolio
        ↓
CustomerScoreInputProvider
        ↓
CustomerScoreInput
        ↓
Component Calculators
        ↓
CustomerScoreCalculator
        ↓
ScoreResult
        ↓
CustomerScore + CustomerScoreSnapshot
```

## Responsabilidades

- `CustomerScoreInputProvider`: busca dados e produz indicadores brutos.
- `CustomerScoreInput`: objeto imutável.
- Calculadores de componentes: aplicam faixas e pesos.
- `CustomerScoreCalculator`: coordena e soma.
- `CustomerScore`: estado atual.
- `CustomerScoreSnapshot`: histórico imutável.

## Regras

- O calculador não acessa banco.
- Fórmulas possuem versão.
- Atualização do score atual e snapshot ocorre na mesma transação.
- Recalcular após alterações financeiras, importações e diariamente.
