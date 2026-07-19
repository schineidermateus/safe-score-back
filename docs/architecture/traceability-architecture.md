# Traceability Architecture

> Especificação oficial da arquitetura de rastreabilidade da Stone Platform.

## 1. Objetivo

Garantir a rastreabilidade completa dos materiais e processos, desde a origem na pedreira até o produto final, preservando a genealogia dos dados e permitindo auditoria e investigação.

---

## 2. Princípios

- Toda transformação preserva a origem.
- Nenhum vínculo histórico é perdido.
- A rastreabilidade é imutável.
- Todo evento pertence a um tenant.
- Custos acompanham a cadeia de transformação.

---

## 3. Cadeia de Rastreabilidade

```text
Pedreira
   ↓
Bloco
   ↓
Ordem de Produção
   ↓
Chapa
   ↓
Lote
   ↓
Estoque
   ↓
Expedição
```

Cada etapa mantém referência à anterior.

---

## 4. Modelo Conceitual

Principais entidades:

- Quarry
- Block
- ProductionOrder
- Slab
- Lot
- InventoryMovement
- Shipment

Os relacionamentos devem permitir navegação em ambos os sentidos (origem → destino e destino → origem).

---

## 5. Eventos de Transformação

Exemplos:

- BlockReceived
- ProductionStarted
- SlabGenerated
- LotCreated
- InventoryMoved
- ShipmentDispatched

Cada evento deve registrar data, usuário, tenant e correlation_id.

---

## 6. Consultas

O sistema deve responder perguntas como:

- De qual pedreira veio esta chapa?
- Quais chapas foram produzidas deste bloco?
- Em quais lotes este material foi utilizado?
- Qual ordem gerou este produto?
- Onde este lote está armazenado?

---

## 7. Custos e Preços

A cadeia de rastreabilidade deve permitir:

- composição histórica de custos;
- identificação da origem dos custos;
- reconstrução do preço aplicado.

---

## 8. Auditoria

Toda transformação relevante deve gerar evento de auditoria.

Auditoria e rastreabilidade são complementares, mas independentes.

---

## 9. Multi-tenancy

Toda cadeia pertence a um único tenant.

Não é permitido relacionar entidades de organizações diferentes.

---

## 10. Persistência

Os relacionamentos devem preservar integridade referencial.

Evitar duplicação de vínculos.

Utilizar índices para consultas frequentes.

---

## 11. Performance

Boas práticas:

- índices por tenant;
- índices por relacionamentos;
- paginação em consultas extensas;
- projeções para relatórios complexos.

---

## 12. API

Disponibilizar endpoints para consulta da genealogia dos materiais.

As respostas devem apresentar a cadeia de origem de forma consistente.

---

## 13. Observabilidade

Registrar:

- duração das consultas;
- correlation_id;
- quantidade de registros percorridos;
- falhas.

---

## 14. Testes

Cobrir:

- criação da cadeia;
- consultas de origem;
- consultas de destino;
- isolamento entre tenants;
- integridade dos vínculos.

---

## 15. Anti-patterns

Não permitido:

- sobrescrever histórico;
- perder vínculo de origem;
- relacionar tenants diferentes;
- excluir registros que quebram a cadeia.

---

## 16. Checklist

- [ ] Origem preservada
- [ ] Destino rastreável
- [ ] Tenant validado
- [ ] Auditoria integrada
- [ ] Índices revisados

---

## 17. Critérios de Aceite

1. Toda transformação preserva a genealogia.
2. A origem pode ser reconstruída.
3. A cadeia respeita o tenant.
4. Custos permanecem vinculados à origem.
5. Consultas apresentam desempenho adequado.

---

## 18. Invariantes

1. Nenhum vínculo histórico é removido.
2. Toda entidade pertence a uma única cadeia de origem.
3. Toda cadeia pertence a um tenant.
4. Auditoria e rastreabilidade permanecem sincronizadas.
5. O histórico pode ser reconstruído integralmente.
