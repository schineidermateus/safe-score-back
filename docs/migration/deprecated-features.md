# Features removidas

A plataforma usa banco novo e não oferece compatibilidade de schema ou dados com o SafeScore.

Foram removidos os módulos, APIs, migrations, fixtures e testes específicos de:

- customers;
- credit limits;
- receivables e payments;
- financial indicators;
- exposure, delinquency e score;
- importadores CSV financeiros.

As rotas `/api/v1/customers`, `/api/v1/credit-limits`, `/api/v1/receivables` e `/api/v1/customers/{id}/financial-summary` não fazem parte do produto novo.
