# Financial Indicators Architecture

## Fluxo

```text
Organization + Customer + ReferenceDate
        ↓
queries agregadas tenant-scoped
        ↓
calculators puros
        ↓
CustomerFinancialIndicators
        ├── API de resumo financeiro
        └── CustomerScoreInputProvider
```

Os calculators não acessam banco nem relógio. A camada de aplicação coordena uma referência única e o módulo de infraestrutura executa somas, contagens e máximos no MySQL.

## Unitário e lote

O cálculo unitário consulta o Customer, o limite vigente, seu agregado financeiro, a exposição da organização e timestamps de atualização. O cálculo em lote carrega Customers, limites e agregados em mapas, com quantidade constante de queries em relação ao número de Customers.

Nenhum Receivable é carregado individualmente para somas.

## Exposição e vencimento

Recebíveis com saldo aberto e não cancelados entram na exposição. Vencido exige `due_date < reference_date`. `OVERDUE` permanece derivado e não é persistido.

## Concentração

O denominador é sempre a exposição total da organização explicitamente recebida. Uma carteira sem exposição retorna `NO_PORTFOLIO`.

## Freshness

A última importação financeira concluída é um sinal organizacional de atualização da fonte. Como o MVP não registra cobertura por Customer em um batch, ela pode atualizar o freshness de todos os Customers da organização. Essa limitação deve ser reavaliada se importações parciais se tornarem comuns.

## Contrato HTTP

```text
GET /api/v1/customers/{id}/financial-summary?reference_date=YYYY-MM-DD
```

A rota é tenant-scoped, exige `FINANCIAL_INDICATORS_READ` e não expõe `organization_id`. Valores indisponíveis são objetos com `status` e `value = null`.
