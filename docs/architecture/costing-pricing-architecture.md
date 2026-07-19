# Costing & Pricing Architecture

> Especificação oficial da arquitetura de custos e precificação da Stone Platform.

## 1. Objetivo

Definir como os custos são calculados, acumulados, distribuídos e utilizados para formação de preços, garantindo rastreabilidade, consistência e histórico.

---

## 2. Princípios

- O backend calcula todos os custos oficiais.
- Custos e preços são versionados.
- Nenhum cálculo oficial ocorre no frontend.
- Todo cálculo é auditável.
- Alterações preservam histórico.

---

## 3. Fluxo Geral

```text
Entrada de Custos
        ↓
Produção
        ↓
Rateio
        ↓
Custo por Bloco
        ↓
Custo por Chapa
        ↓
Custo por Lote
        ↓
Política de Preço
        ↓
Preço Final
```

---

## 4. Fontes de Custo

- matéria-prima;
- transporte;
- energia;
- mão de obra;
- manutenção;
- insumos;
- perdas;
- despesas indiretas.

---

## 5. Modelo de Custos

Categorias:

- Diretos;
- Indiretos;
- Fixos;
- Variáveis.

Cada lançamento deve possuir origem, data e tenant.

---

## 6. Rateio

O mecanismo de rateio deve ser configurável.

Exemplos:

- por peso;
- por área;
- por quantidade;
- por tempo de máquina.

---

## 7. Transformações

A cada transformação o custo acompanha a cadeia de rastreabilidade.

Exemplo:

```text
Bloco
 ↓
Chapas
 ↓
Lote
```

---

## 8. Políticas de Preço

As políticas podem considerar:

- margem fixa;
- markup;
- tabela comercial;
- cliente;
- região;
- vigência.

---

## 9. Histórico

Custos e preços nunca são sobrescritos.

Toda alteração gera nova versão.

---

## 10. Auditoria

Registrar:

- cálculo executado;
- política aplicada;
- usuário;
- tenant;
- correlation_id.

---

## 11. API

Disponibilizar consultas para:

- custo atual;
- custo histórico;
- composição do custo;
- preço vigente;
- simulações (quando permitido).

---

## 12. Performance

- reutilizar cálculos quando possível;
- evitar recálculos desnecessários;
- utilizar processamento assíncrono para operações massivas.

---

## 13. Observabilidade

Registrar:

- tempo de cálculo;
- falhas;
- quantidade de itens processados;
- correlation_id.

---

## 14. Testes

Cobrir:

- cálculo de custo;
- rateios;
- políticas;
- histórico;
- arredondamentos;
- isolamento por tenant.

---

## 15. Anti-patterns

Não permitido:

- cálculos no frontend;
- sobrescrever histórico;
- políticas sem versionamento;
- mistura de custos entre tenants.

---

## 16. Checklist

- [ ] Custos rastreáveis
- [ ] Política aplicada
- [ ] Histórico preservado
- [ ] Auditoria registrada
- [ ] Testes implementados

---

## 17. Critérios de Aceite

1. Todo custo possui origem identificável.
2. O preço pode ser reconstruído.
3. Histórico permanece íntegro.
4. Multi-tenancy é respeitado.
5. Cálculos são reproduzíveis.

---

## 18. Invariantes

1. O backend é a única fonte oficial de custos.
2. Nenhum cálculo oficial ocorre no frontend.
3. Custos acompanham a cadeia de rastreabilidade.
4. Preços são derivados de políticas versionadas.
5. Toda alteração relevante é auditada.
