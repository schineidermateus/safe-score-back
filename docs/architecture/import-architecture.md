# Import Architecture

> Especificação oficial da arquitetura de importações da Stone Platform.

## 1. Objetivo

Definir uma arquitetura segura, escalável e resiliente para importações em lote.

---

## 2. Princípios

- Upload não executa importação.
- Toda importação é assíncrona.
- Processamento é idempotente.
- Falhas são rastreáveis.
- Usuário acompanha o progresso.

---

## 3. Fluxo Geral

```text
Upload
  ↓
Validação inicial
  ↓
Registro da Importação
  ↓
Storage
  ↓
Fila (Messenger)
  ↓
Processamento
  ↓
Persistência
  ↓
Relatório
```

---

## 4. Estados

```text
UPLOADED
↓
VALIDATING
↓
QUEUED
↓
PROCESSING
↓
COMPLETED
```

Estados de erro:

```text
FAILED
CANCELLED
PARTIAL_SUCCESS
```

---

## 5. Componentes

- Import Controller
- Import Service
- Import Job
- Import Parser
- Validator
- Row Processor
- Import Report
- File Storage

---

## 6. Upload

Aceitar apenas formatos suportados.

Registrar:

- usuário;
- tenant;
- nome;
- hash;
- tamanho;
- tipo.

---

## 7. Armazenamento

Salvar arquivo em storage antes do processamento.

Exemplo:

```text
organizations/{tenant}/imports/{uuid}.xlsx
```

---

## 8. Validação

Validar:

- extensão;
- MIME type;
- tamanho;
- layout;
- colunas obrigatórias.

Falhas impedem o enfileiramento.

---

## 9. Processamento

Executado via Symfony Messenger.

Nunca bloquear a requisição HTTP.

---

## 10. Processamento por Linha

Cada linha deve:

- validar dados;
- aplicar regras;
- persistir;
- registrar erros.

Uma linha inválida não deve interromper toda a importação, quando a regra permitir.

---

## 11. Idempotência

Importações repetidas devem evitar duplicidade.

Estratégias:

- hash do arquivo;
- chave natural;
- chave técnica.

---

## 12. Progresso

Registrar:

- total;
- processadas;
- sucesso;
- erro;
- percentual.

---

## 13. Relatórios

Gerar relatório contendo:

- linhas importadas;
- linhas rejeitadas;
- motivo;
- timestamp.

---

## 14. Auditoria

Registrar:

- início;
- término;
- usuário;
- tenant;
- arquivo;
- resultado.

---

## 15. Observabilidade

Registrar:

- duração;
- throughput;
- falhas;
- retries;
- correlation_id.

---

## 16. Recuperação

Permitir:

- retry;
- reprocessamento;
- cancelamento (quando aplicável).

---

## 17. Segurança

- autenticação obrigatória;
- autorização por capability;
- isolamento por tenant;
- arquivos protegidos.

---

## 18. Testes

Cobrir:

- upload;
- parser;
- validação;
- processamento;
- retry;
- erros;
- idempotência.

---

## 19. Anti-patterns

Não permitido:

- importar na thread HTTP;
- ignorar erros;
- arquivos sem tenant;
- processamento sem auditoria;
- duplicação por reenvio.

---

## 20. Checklist

- [ ] Arquivo validado
- [ ] Job criado
- [ ] Contexto do tenant propagado
- [ ] Progresso registrado
- [ ] Auditoria criada
- [ ] Relatório disponível

---

## 21. Critérios de Aceite

1. Upload responde rapidamente.
2. Processamento é assíncrono.
3. Erros são identificados por linha.
4. Importações são auditáveis.
5. Idempotência é garantida.
6. Progresso pode ser consultado.

---

## 22. Invariantes

1. Toda importação pertence a um tenant.
2. Nenhuma importação executa na requisição HTTP.
3. Todo processamento gera auditoria.
4. Arquivos permanecem protegidos.
5. O usuário pode consultar o resultado final.
