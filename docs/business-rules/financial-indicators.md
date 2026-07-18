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

## Referência e limitações

`reference_date` é uma data civil obrigatória e única para toda a execução. Ela define vigência, vencimento e atraso sobre os saldos atualmente persistidos. O MVP não reconstrói uma posição financeira histórica anterior a pagamentos ou alterações de saldo.

## Estados de ausência

```text
AVAILABLE
NO_ACTIVE_LIMIT
NO_EXPOSURE
INSUFFICIENT_HISTORY
INCONSISTENT_DATA
NO_PORTFOLIO
```

Ausência de limite, exposição, histórico ou carteira nunca é convertida silenciosamente em um percentual real.

## Precisão

- dinheiro: string decimal com duas casas;
- percentuais: BCMath com seis casas intermediárias e arredondamento half-away-from-zero para duas casas na saída;
- dias máximos: inteiro não negativo;
- média de atraso: string decimal com duas casas;
- utilização pode superar 100%;
- crédito disponível pode ser negativo.

## Histórico de pagamento

Pontualidade usa a quitação final persistida em `Receivable.payment_date`. Pagamentos parciais não contam como título pago. Títulos pagos sem data de quitação não participam dos percentuais. Sem títulos pagos, percentual e atrasos ficam indisponíveis.

## Qualidade V1

Critérios objetivos:

| Critério | Pontos |
|---|---:|
| documento presente | 20 |
| limite ativo consistente | 20 |
| ao menos um recebível | 20 |
| ao menos três títulos pagos | 20 |
| atualização até 30 dias | 20 |
| atualização entre 31 e 90 dias | 10 |
| atualização acima de 90 dias ou ausente | 0 |

O resultado inclui score, nível e reason codes. Qualidade não é confiança nem score de risco.

## Última atualização

`last_data_update` é o maior timestamp de atualização do saldo de recebíveis, alteração de limites ou conclusão de importação financeira. Datas de emissão, vencimento, pagamento e vigência são datas de negócio e não representam atualização do sistema.
