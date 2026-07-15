# SafeScore — Business Rules

## 1. Conceitos

### Limite de crédito
Valor máximo aprovado para exposição financeira de um cliente.

### Exposição financeira
Valor financeiro atualmente comprometido com um cliente.

No MVP:

```text
Exposição = soma do saldo em aberto dos recebíveis elegíveis
```

### Crédito disponível

```text
Crédito disponível = limite ativo - exposição
```

### Percentual de utilização

```text
Percentual de utilização = exposição / limite ativo × 100
```

Quando o limite for zero ou inexistente, o percentual não deve ser calculado.

## 2. Recebíveis elegíveis

Entram na exposição:

- OPEN;
- PARTIALLY_PAID;
- OVERDUE.

Não entram:

- PAID;
- CANCELLED.

## 3. Status do recebível

- `OPEN`: em aberto e ainda não vencido.
- `PARTIALLY_PAID`: parcialmente pago.
- `PAID`: integralmente pago.
- `OVERDUE`: possui saldo em aberto e vencimento anterior à data de referência.
- `CANCELLED`: cancelado e desconsiderado nos cálculos.

O status `OVERDUE` pode ser recalculado diariamente.

## 4. Limite ativo

Um cliente pode ter vários registros históricos de limite, mas apenas um limite vigente por data de referência.

Um limite é vigente quando:

```text
valid_from <= data de referência
e
valid_until é nulo ou valid_until >= data de referência
e
status = ACTIVE
```

Não pode haver sobreposição entre períodos ativos do mesmo cliente.

## 5. Aging

Faixas iniciais:

- A vencer.
- 1 a 15 dias em atraso.
- 16 a 30 dias.
- 31 a 60 dias.
- 61 a 90 dias.
- Acima de 90 dias.

O número de dias em atraso é:

```text
data de referência - due_date
```

Somente títulos com saldo aberto participam do aging.

## 6. Alertas

### Limite em atenção
Gerado quando a utilização atingir ou ultrapassar 80%.

### Limite crítico
Gerado quando a utilização atingir ou ultrapassar 100%.

### Cliente sem limite
Gerado quando houver exposição e nenhum limite ativo.

### Recebível vencido
Gerado quando houver saldo em aberto após o vencimento.

### Atraso relevante
Gerado quando houver recebível com atraso superior a 30 dias.

### Concentração elevada
Gerado quando a exposição do cliente representar percentual superior ao limite configurado da exposição total da carteira.

Valor padrão inicial: 20%.

## 7. Severidade dos alertas

- `INFO`: situação informativa.
- `WARNING`: requer atenção.
- `CRITICAL`: requer ação prioritária.

Exemplo:

- 80% a 99,99% do limite: WARNING.
- 100% ou mais: CRITICAL.
- atraso superior a 30 dias: CRITICAL.

## 8. Ciclo de vida do alerta

- `OPEN`;
- `ACKNOWLEDGED`;
- `RESOLVED`;
- `DISMISSED`.

Ao resolver ou descartar um alerta, o usuário deve poder registrar observação.

Alertas baseados em condição podem ser reabertos ou recriados se a condição voltar a ocorrer.

## 9. Concentração da carteira

```text
Concentração do cliente =
exposição do cliente / exposição total da organização × 100
```

Clientes sem exposição não participam.

## 10. Importação

### Identificação do cliente

Prioridade:

1. CNPJ/CPF normalizado.
2. Identificador externo.
3. Combinação manual assistida.

### Identificação do recebível

Preferência:

```text
organization_id + source + external_id
```

Quando não houver identificador externo, gerar chave determinística com campos relevantes.

### Idempotência

Reimportar o mesmo arquivo ou registro não pode duplicar clientes ou recebíveis.

### Validação mínima

- documento válido quando informado;
- valor original maior ou igual a zero;
- saldo aberto maior ou igual a zero;
- saldo aberto menor ou igual ao valor original;
- data de vencimento válida;
- cliente identificável;
- status reconhecido.

## 11. Auditoria

Devem ser auditadas:

- criação e alteração de limites;
- alteração manual de recebíveis;
- resolução de alertas;
- alteração de permissões;
- importações;
- exclusões lógicas.

A auditoria deve registrar:

- organização;
- usuário;
- ação;
- entidade;
- identificador;
- dados anteriores;
- dados posteriores;
- data e hora.

## 12. Multi-tenancy

Todos os dados de negócio pertencem a uma organização.

Nenhum identificador enviado pelo frontend pode permitir acesso a dados de outra organização.

A organização deve ser obtida do contexto autenticado.

## 13. Moeda e arredondamento

- Moeda inicial: BRL.
- Persistência monetária em decimal.
- Nunca utilizar ponto flutuante binário para valores financeiros.
- Arredondamento padrão: duas casas decimais.
