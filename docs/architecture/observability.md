# Observability Architecture

> Especificação oficial da arquitetura de observabilidade da Stone Platform.

## 1. Objetivo

Garantir visibilidade operacional do sistema por meio de logs, métricas, traces e alertas, permitindo identificar, diagnosticar e resolver problemas rapidamente.

---

## 2. Princípios

- Observabilidade é um requisito arquitetural.
- Todo evento relevante deve ser correlacionável.
- Logs não substituem auditoria.
- Dados sensíveis não devem ser registrados.
- Toda telemetria respeita o isolamento por tenant.

---

## 3. Pilares

- Logs estruturados
- Métricas
- Tracing distribuído
- Alertas

---

## 4. Correlation ID

Toda requisição recebe um `correlation_id`.

Fluxo:

```text
HTTP
 ↓
Application
 ↓
Messenger Jobs
 ↓
Integrações
 ↓
Logs / Traces / Auditoria
```

O mesmo identificador acompanha toda a operação.

---

## 5. Logs

Registrar:

- timestamp;
- nível;
- mensagem;
- tenant;
- usuário;
- endpoint;
- correlation_id.

Nunca registrar:

- senhas;
- tokens;
- segredos;
- dados pessoais desnecessários.

---

## 6. Métricas

Exemplos:

- tempo de resposta;
- throughput;
- erros por endpoint;
- jobs processados;
- filas pendentes;
- uploads;
- importações.

---

## 7. Tracing

Instrumentar:

- requisições HTTP;
- banco de dados;
- filas;
- integrações externas;
- storage.

---

## 8. Alertas

Alertar para:

- aumento de erros;
- filas paradas;
- falhas de importação;
- indisponibilidade de integrações;
- degradação de performance.

---

## 9. Banco de Dados

Monitorar:

- consultas lentas;
- locks;
- conexões;
- utilização de índices.

---

## 10. API

Registrar:

- método;
- rota;
- status HTTP;
- duração;
- tamanho da resposta.

---

## 11. Messenger

Monitorar:

- tamanho das filas;
- retries;
- falhas;
- tempo médio de processamento.

---

## 12. Auditoria

Relacionar logs e auditoria por `correlation_id`, mantendo responsabilidades distintas.

---

## 13. Dashboards

Disponibilizar painéis para:

- API;
- banco;
- filas;
- importações;
- infraestrutura.

---

## 14. Retenção

Definir políticas de retenção para:

- logs;
- métricas;
- traces.

Conforme requisitos operacionais e legais.

---

## 15. Testes

Validar:

- geração de logs;
- propagação do correlation_id;
- métricas;
- tracing;
- alertas.

---

## 16. Anti-patterns

Não permitido:

- logs em texto livre sem estrutura;
- ausência de correlation_id;
- registrar segredos;
- usar auditoria como log técnico.

---

## 17. Checklist

- [ ] Correlation ID propagado
- [ ] Logs estruturados
- [ ] Métricas coletadas
- [ ] Tracing habilitado
- [ ] Alertas configurados
- [ ] Dashboards disponíveis

---

## 18. Critérios de Aceite

1. Toda requisição é rastreável.
2. Logs são estruturados.
3. Métricas permitem monitoramento operacional.
4. Traces conectam componentes distribuídos.
5. Alertas detectam falhas relevantes.

---

## 19. Invariantes

1. Toda operação possui correlation_id.
2. Logs não substituem auditoria.
3. Dados sensíveis nunca são registrados.
4. Observabilidade respeita multi-tenancy.
5. Eventos críticos são monitorados.
