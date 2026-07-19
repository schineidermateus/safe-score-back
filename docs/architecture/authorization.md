# Authorization Architecture

> Especificação oficial da arquitetura de autenticação e autorização da Stone Platform.

## 1. Objetivo

Garantir que toda operação protegida seja autenticada e autorizada de forma consistente, centralizada e auditável.

---

## 2. Princípios

- Autenticação identifica o usuário.
- Autorização verifica capacidades (capabilities).
- O backend é a única autoridade de autorização.
- O frontend apenas adapta a interface.

---

## 3. Conceitos

```text
User
  ↓
Membership
  ↓
Organization
  ↓
Role(s)
  ↓
Capability(s)
  ↓
Protected Resource
```

Um usuário pode possuir papéis diferentes em organizações distintas.

---

## 4. Fluxo

```text
HTTP Request
    ↓
JWT
    ↓
Authentication
    ↓
Tenant Resolution
    ↓
Capability Check
    ↓
Use Case
```

---

## 5. Autenticação

Utilizar access token JWT externo stateless assinado com RS256. O frontend autentica no provedor de identidade e envia o token como Bearer. O backend verifica a assinatura com o JWKS configurado e resolve o perfil local exclusivamente por issuer e subject. O backend não recebe senha e não possui chave privada de autenticação.

Após autenticação, o backend deve conhecer:

- issuer e subject externos
- usuário local ativo
- organization_id ativo, quando a claim opcional estiver presente e for confirmada localmente
- memberships
- capabilities efetivas

O `organization_id` externo nunca é fonte de autoridade: quando presente, é apenas uma indicação revalidada contra organização e membership locais. Sem a claim, uma única membership ativa pode ser selecionada de forma não ambígua. Usuários com múltiplas memberships dependem do contrato explícito de seleção a ser definido na Spec 01.

---

## 6. Capabilities

Permissões são modeladas por capabilities.

Exemplos:

```text
BLOCK_READ
BLOCK_CREATE
BLOCK_UPDATE
BLOCK_DELETE

PRODUCTION_EXECUTE
IMPORT_EXECUTE
AUDIT_READ
PRICING_MANAGE
```

---

## 7. Papéis

Papéis agrupam capabilities.

Exemplo:

```text
Administrator
ProductionManager
Operator
Commercial
Auditor
```

O código verifica capabilities, não nomes de papéis.

---

## 8. Symfony Security

Utilizar:

- Security Component
- Authenticators
- Access Control
- Voters (quando apropriado)

Evitar lógica de autorização em controllers.

---

## 9. Policies

Regras complexas devem ser encapsuladas em Policies.

Exemplo:

```php
CanCloseLotPolicy
CanEditPricePolicy
```

---

## 10. Voters

Voters são úteis para decisões envolvendo um recurso específico.

Exemplos:

- editar bloco;
- visualizar auditoria;
- alterar ordem.

---

## 11. Controllers

Controllers apenas:

- autenticam;
- delegam;
- retornam respostas.

Nunca implementam regras de autorização.

---

## 12. API

Respostas:

- 401 → não autenticado
- 403 → autenticado, porém sem permissão

Formato consistente:

```json
{
  "code":"FORBIDDEN",
  "message":"Operation not allowed."
}
```

---

## 13. Frontend

O frontend pode ocultar:

- menus;
- botões;
- ações.

Isso melhora UX, mas nunca substitui validações do backend.

---

## 14. Auditoria

Toda negação relevante pode registrar:

- usuário;
- tenant;
- capability;
- recurso;
- timestamp;
- correlation_id.

---

## 15. Testes

Cobrir:

- autenticação;
- capabilities;
- múltiplos papéis;
- múltiplos tenants;
- respostas 401;
- respostas 403.

---

## 16. Anti-patterns

Não permitido:

- confiar no frontend;
- verificar papéis diretamente;
- duplicar regras;
- autorização em JavaScript;
- controllers com lógica de permissão.

---

## 17. Checklist

- [ ] JWT validado
- [ ] Tenant resolvido
- [ ] Capability verificada
- [ ] Resposta padronizada
- [ ] Auditoria disponível
- [ ] Testes automatizados

---

## 18. Critérios de Aceite

1. Toda rota protegida exige autenticação.
2. Toda ação verifica capabilities.
3. Papéis apenas agrupam permissões.
4. Frontend nunca decide autorização.
5. Regras permanecem centralizadas.

---

## 19. Invariantes

1. O backend é a fonte de verdade para autorização.
2. Nenhuma ação protegida ignora capabilities.
3. Usuários nunca acessam recursos de outro tenant.
4. Controllers permanecem livres de lógica de autorização.
5. Toda decisão é reproduzível e testável.
