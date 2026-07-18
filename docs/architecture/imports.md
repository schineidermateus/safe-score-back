# Imports

## Processamento do MVP

As importações de `CUSTOMERS`, `CREDIT_LIMITS` e `RECEIVABLES` são síncronas. O fluxo é:

```text
Upload -> Mapping -> Validate -> Preview -> Process
```

O parser lê o CSV por stream. Os limites iniciais são:

- 2 MB por arquivo;
- 2.000 linhas de dados;
- 50 colunas;
- 10 KB por célula;
- 100 linhas por página de preview;
- 100 itens no máximo por página da API.

Os limites de aplicação são inferiores aos 20 MB aceitos atualmente pelo PHP e pelo Nginx.

## Armazenamento

Arquivos são armazenados fora de `public`, em `var/imports`, com chave aleatória gerada pelo backend. O nome original é apenas metadado sanitizado e nenhum caminho absoluto é exposto pela API.

Em produção, `var/imports` deve ser montado como volume persistente e gravável apenas pelo backend, por exemplo:

```text
import_files:/app/var/imports
```

O Nginx não deve montar ou servir esse volume.

## Retenção sugerida

Não existe job de limpeza nesta spec. A política inicial recomendada é:

- arquivo original: 30 dias após conclusão, cancelamento ou falha;
- `raw_data`, `normalized_data` e erros por linha: 90 dias;
- metadados do lote: 90 dias ou conforme política regulatória;
- auditoria: conforme a política geral de auditoria do SafeScore.

Uma futura rotina de limpeza deve preservar auditoria, nunca remover arquivos de lotes em processamento e operar sempre com escopo explícito de organização.

## Idempotência

- Arquivo: SHA-256 por organização e tipo; conteúdo já concluído é rejeitado.
- Customer: `organization_id + external_id` ou `organization_id + document`.
- CreditLimit: cliente, valor, período, razão e status ativo; idêntico é descartado e sobreposição diferente é erro.
- Receivable: `organization_id + source + external_id`.

No MVP, recebíveis importados devem estar inicialmente abertos, sem pagamentos. Histórico de pagamentos nunca é reconstituído a partir de saldos agregados.
