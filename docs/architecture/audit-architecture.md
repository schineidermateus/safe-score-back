# Audit Architecture

> Especificação oficial da arquitetura de auditoria da Stone Platform.

## 1. Objetivo

Garantir rastreabilidade completa das ações relevantes realizadas no sistema, preservando histórico imutável para fins operacionais, legais e de investigação.

---

## 2. Princípios

- Auditoria é imutável.
- Auditoria não substitui logs técnicos.
- Apenas eventos relevantes são auditados.
- Toda auditoria pertence a um tenant.
- Auditoria é consultável.

---

## 3. Escopo

Devem ser auditados:

- criação;
- alteração;
- exclusão lógica;
- autenticação relevante;
- importações;
- mudanças de configuração;
- alterações de permissões;
- operações críticas.

---

## 4. Fluxo

```text
HTTP Request
    ↓
Use Case
    ↓
Domain Event
    ↓
Audit Service
    ↓
Audit Repository
    ↓
Database
```

---

## 5. Modelo

Campos mínimos:

```text
id
organization_id
user_id
resource
resource_id
action
timestamp
correlation_id
metadata
```

---

## 6. Ações

Exemplos:

```text
BLOCK_CREATED
BLOCK_UPDATED
LOT_CLOSED
IMPORT_COMPLETED
USER_LOGIN
ROLE_CHANGED
```

---

## 7. Origem

Toda auditoria deve indicar:

- usuário;
- tenant;
- origem (API, Job, CLI);
- IP (quando aplicável);
- correlation_id.

---

## 8. Metadata

Dados adicionais devem ser armazenados em estrutura flexível (JSON), evitando duplicação excessiva.

---

## 9. Domain Events

Preferencialmente a auditoria é acionada por Domain Events, desacoplando os casos de uso.

---

## 10. Consultas

Permitir filtros por:

- período;
- usuário;
- recurso;
- ação;
- tenant;
- correlation_id.

---

## 11. Retenção

Definir política de retenção conforme requisitos legais e operacionais.

Nunca excluir registros por fluxos comuns da aplicação.

---

## 12. Segurança

Somente usuários autorizados podem consultar auditoria.

Capabilities sugeridas:

```text
AUDIT_READ
AUDIT_EXPORT
```

---

## 13. Performance

- índices por tenant e data;
- paginação obrigatória;
- exportações assíncronas.

---

## 14. LGPD

Não registrar:

- senhas;
- tokens;
- segredos;
- dados sensíveis desnecessários.

Mas preservar evidências suficientes para investigação.

---

## 15. Observabilidade

Relacionar auditoria com:

- logs;
- traces;
- métricas;

através do `correlation_id`.

---

## 16. Testes

Cobrir:

- criação de eventos;
- integridade;
- consultas;
- isolamento entre tenants;
- permissões.

---

## 17. Anti-patterns

Não permitido:

- editar auditoria;
- apagar registros comuns;
- usar auditoria como log técnico;
- registrar informações sigilosas.

---

## 18. Checklist

- [ ] Evento auditável identificado
- [ ] Correlation ID registrado
- [ ] Tenant registrado
- [ ] Capability protegida
- [ ] Índices revisados

---

## 19. Critérios de Aceite

1. Auditoria é imutável.
2. Toda ação relevante gera registro.
3. Consultas são eficientes.
4. Multi-tenancy é respeitado.
5. Dados sensíveis permanecem protegidos.

---

## 20. Invariantes

1. Nenhum registro de auditoria é alterado.
2. Toda auditoria pertence a um tenant.
3. Correlation ID acompanha operações distribuídas.
4. Auditoria não substitui logs técnicos.
5. Consultas respeitam autorização.
