# SafeScore MVP — Acceptance Test Scenarios

## Cenário 1 — Cliente abaixo do limite

Dado um cliente com limite de R$ 100.000  
E recebíveis em aberto de R$ 60.000  
Quando o resumo for calculado  
Então a exposição será R$ 60.000  
E o crédito disponível será R$ 40.000  
E a utilização será 60%  
E nenhum alerta de limite será crítico.

## Cenário 2 — Cliente em atenção

Dado limite de R$ 100.000  
E exposição de R$ 85.000  
Então deve existir alerta WARNING de utilização elevada.

## Cenário 3 — Cliente acima do limite

Dado limite de R$ 100.000  
E exposição de R$ 120.000  
Então o crédito disponível será -R$ 20.000  
E deve existir alerta CRITICAL.

## Cenário 4 — Exposição sem limite

Dado um cliente sem limite ativo  
E exposição maior que zero  
Então deve existir alerta de cliente sem limite.

## Cenário 5 — Título vencido

Dado um título com vencimento anterior à data de referência  
E saldo em aberto maior que zero  
Então seu status calculado deve ser OVERDUE  
E seu valor deve entrar no aging.

## Cenário 6 — Importação repetida

Dado um arquivo já processado  
Quando o mesmo arquivo for importado novamente  
Então nenhum recebível deve ser duplicado  
E o resultado deve indicar registros ignorados ou atualizados.

## Cenário 7 — Isolamento de organização

Dado usuário da Organização A  
Quando ele tentar acessar cliente da Organização B  
Então a API deve retornar 404 ou 403  
E nenhum dado da Organização B deve ser exposto.
