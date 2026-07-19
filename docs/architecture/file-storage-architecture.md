# File Storage Architecture

> Especificação oficial da arquitetura de armazenamento de arquivos da Stone Platform.

## 1. Objetivo

Definir a arquitetura para armazenamento, recuperação e gerenciamento de arquivos, garantindo segurança, isolamento por tenant, rastreabilidade e independência do provedor de storage.

---

## 2. Princípios

- O domínio nunca acessa o provedor de storage diretamente.
- Todo arquivo pertence a um tenant.
- O banco armazena apenas metadados.
- Arquivos são acessados por serviços da aplicação.
- O provedor pode ser substituído sem impacto no domínio.

---

## 3. Casos de Uso

- Importações
- Exportações
- Relatórios
- Anexos
- Documentos
- Imagens
- Arquivos temporários

---

## 4. Arquitetura

```text
Application
    │
File Storage Service
    │
Storage Provider
    ├── Local
    ├── S3
    ├── Azure Blob
    └── Outros
```

---

## 5. Organização

Estrutura sugerida:

```text
organizations/
 └── {organization_id}/
      ├── imports/
      ├── exports/
      ├── reports/
      ├── attachments/
      └── temp/
```

---

## 6. Metadados

Persistir:

- id
- organization_id
- filename
- original_filename
- mime_type
- size
- checksum
- storage_path
- created_at
- uploaded_by

---

## 7. Upload

Fluxo:

```text
Upload
 ↓
Validação
 ↓
Storage
 ↓
Persistência dos metadados
 ↓
Resposta
```

Validar tamanho, extensão e MIME type.

---

## 8. Download

Todo download deve:

- exigir autenticação;
- validar capabilities;
- validar tenant;
- registrar auditoria quando necessário.

Nunca expor caminhos físicos.

---

## 9. Exclusão

A exclusão lógica é preferível quando houver necessidade de histórico.

Arquivos removidos devem respeitar a política de retenção.

---

## 10. Segurança

- isolamento por tenant;
- nomes internos imprevisíveis;
- checksum para integridade;
- antivírus (quando aplicável);
- URLs temporárias quando suportadas.

---

## 11. Observabilidade

Registrar:

- uploads;
- downloads;
- falhas;
- tempo de transferência;
- correlation_id.

---

## 12. Auditoria

Auditar:

- upload;
- download sensível;
- exclusão;
- substituição;
- exportações.

---

## 13. Performance

- streaming para arquivos grandes;
- uploads multipart quando suportado;
- evitar carregar arquivos inteiros em memória.

---

## 14. Testes

Cobrir:

- upload;
- download;
- autorização;
- isolamento por tenant;
- checksum;
- provedores de storage.

---

## 15. Anti-patterns

Não permitido:

- caminhos físicos expostos;
- arquivos sem tenant;
- acesso direto do domínio ao storage;
- metadados inconsistentes.

---

## 16. Checklist

- [ ] Provider abstraído
- [ ] Tenant validado
- [ ] Metadados persistidos
- [ ] Auditoria integrada
- [ ] Observabilidade disponível

---

## 17. Critérios de Aceite

1. O provedor pode ser trocado sem alterar o domínio.
2. Nenhum arquivo é acessado sem autorização.
3. Arquivos permanecem isolados por tenant.
4. Metadados permanecem consistentes.
5. Uploads e downloads são auditáveis.

---

## 18. Invariantes

1. Todo arquivo pertence a um tenant.
2. O domínio desconhece o provedor de storage.
3. O banco armazena apenas metadados.
4. O acesso sempre passa pela aplicação.
5. O caminho físico nunca é exposto ao cliente.
