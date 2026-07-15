# Receivables

O módulo mantém `Receivable` como a projeção autoritativa do saldo atual e `ReceivablePayment` como histórico imutável dos eventos que alteraram esse saldo. As colunas `open_amount` e `paid_amount` não possuem fluxo de edição independente: são atualizadas exclusivamente pelo método de domínio que também cria o pagamento, dentro da mesma transação e sob bloqueio pessimista. A igualdade `open_amount + paid_amount = original_amount` permite reconciliação com o histórico.

Todos os valores monetários usam `DECIMAL(19,2)` no MySQL e strings decimais na API. Juros, multas, descontos, abatimentos, estornos e exclusão física estão fora do MVP. A moeda é herdada da organização.

`CANCELLED`, `PAID`, `PARTIALLY_PAID` e `OPEN` são estados persistidos. `OVERDUE` é resolvido dinamicamente com uma data de referência explícita, evitando estado vencido obsoleto. A precedência é: cancelado, pago, vencido, parcialmente pago e aberto. O aging individual usa a mesma referência; pagos e cancelados não recebem faixa. O filtro `overdue=false` inclui qualquer título que não esteja vencido, inclusive pago ou cancelado.

`payment_date` representa a data do pagamento que quitou integralmente o título e nunca pode preceder `issue_date`. Um título de valor original zero fica pago sem inventar um evento ou uma data de pagamento; indicadores futuros de pontualidade devem considerar apenas títulos pagos que possuam `payment_date`.

A chave `(organization_id, source, external_id)` prepara idempotência futura. Criações pela API usam `MANUAL`, aceitam `external_id` nulo e nunca recebem `organization_id` do cliente. Toda leitura ou mutação inclui o tenant, e pagamentos usam bloqueio pessimista para impedir saldo negativo em concorrência.
