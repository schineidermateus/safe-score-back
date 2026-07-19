# API Architecture

> Especificação oficial da arquitetura da API REST da Stone Platform.

## 1. Objetivo

Definir padrões para desenvolvimento, evolução e consumo da API.

---

## 2. Princípios

- REST first.
- Backend é a fonte de verdade.
- Contratos explícitos.
- Versionamento previsível.
- DTOs desacoplados do domínio.
- Respostas consistentes.

---

## 3. Arquitetura

```text
HTTP
  │
Controller
  │
Request DTO
  │
Command / Query
  │
Application
  │
Response DTO
  │
JSON
```

---

## 4. Versionamento

Formato:

```text
/api/v1/
```

Mudanças incompatíveis exigem nova versão.

---

## 5. Recursos

Exemplos:

```text
GET    /api/v1/blocks
POST   /api/v1/blocks
GET    /api/v1/blocks/{id}
PUT    /api/v1/blocks/{id}
DELETE /api/v1/blocks/{id}
```

Usar substantivos no plural.

---

## 6. Controllers

Controllers devem:

- validar entrada;
- delegar ao caso de uso;
- retornar DTO.

Nunca conter regras de negócio.

---

## 7. DTOs

Separar:

```text
CreateBlockRequest
UpdateBlockRequest
BlockResponse
BlockListItem
```

Nunca expor entidades Doctrine.

---

## 8. Serialização

Respostas JSON consistentes.

Datas em ISO-8601.

Valores monetários em decimal.

---

## 9. Paginação

Padrão:

```text
?page=1
&page_size=20
```

Resposta:

```json
{
  "items": [],
  "page": 1,
  "page_size": 20,
  "total": 150
}
```

---

## 10. Filtros

Formato:

```text
?status=ACTIVE
&search=granito
&sort=created_at
&order=desc
```

---

## 11. Autenticação

JWT Bearer.

Cabeçalho:

```text
Authorization: Bearer <token>
```

---

## 12. Autorização

Toda operação protegida valida capabilities antes do caso de uso.

---

## 13. Validação

Erros retornam HTTP 400 ou 422.

Formato padronizado:

```json
{
  "code":"VALIDATION_ERROR",
  "message":"Validation failed.",
  "errors":[]
}
```

---

## 14. Erros

Categorias:

- AUTHENTICATION
- AUTHORIZATION
- VALIDATION
- NOT_FOUND
- CONFLICT
- BUSINESS_RULE
- INTERNAL_ERROR

Sempre incluir `correlation_id`.

---

## 15. Idempotência

Operações sensíveis podem utilizar:

```text
Idempotency-Key
```

Especialmente importações e integrações.

---

## 16. Uploads

Fluxo:

```text
Upload
 ↓
Validação
 ↓
Storage
 ↓
Job
 ↓
Processamento
```

---

## 17. Downloads

Arquivos devem ser recuperados por endpoints autenticados.

Nunca expor caminhos físicos.

---

## 18. OpenAPI

Toda rota documentada.

Gerar documentação automaticamente sempre que possível.

---

## 19. Observabilidade

Registrar:

- duração;
- status HTTP;
- correlation_id;
- endpoint;
- usuário;
- tenant.

---

## 20. Performance

- paginação obrigatória;
- evitar payloads excessivos;
- compressão HTTP;
- eager loading quando necessário.

---

## 21. Testes

Cobrir:

- contratos;
- autenticação;
- autorização;
- erros;
- paginação;
- filtros;
- uploads;
- downloads.

---

## 22. Anti-patterns

Não permitido:

- retornar entidades;
- regras em controllers;
- endpoints sem versionamento;
- respostas inconsistentes;
- stack traces na API.

---

## 23. Checklist

- [ ] Endpoint versionado
- [ ] DTOs separados
- [ ] OpenAPI atualizado
- [ ] Validação implementada
- [ ] Capability verificada
- [ ] Testes criados

---

## 24. Critérios de Aceite

1. API consistente.
2. Contratos estáveis.
3. Erros padronizados.
4. DTOs independentes.
5. Segurança aplicada.
6. Documentação atualizada.

---

## 25. Invariantes

1. Toda rota possui contrato explícito.
2. Nenhuma entidade é exposta.
3. Controllers permanecem finos.
4. O backend controla autenticação e autorização.
5. A API é a única interface pública oficial do backend.
