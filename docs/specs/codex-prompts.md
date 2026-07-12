# SafeScore MVP — Prompts para Codex

## 1. Análise da base Lumi

```text
Analise este projeto Angular originado do Lumi.
Identifique o que pode ser reutilizado no SafeScore:
autenticação, layout, tema, interceptors, componentes e tratamento de erro.
Liste os módulos específicos do Lumi que devem ser removidos.
Não altere arquivos ainda.
Entregue um plano de migração por etapas e riscos.
```

## 2. Fundação frontend

```text
Prepare a estrutura de features do SafeScore:
dashboard, customers, credit-limits, receivables, imports e alerts.
Use standalone components.
Preserve autenticação, layout e interceptors existentes.
Não implemente regras de negócio.
Adicione rotas lazy e páginas placeholder.
```

## 3. Módulo de clientes

```text
Implemente a feature Customers conforme docs/product-scope.md,
docs/domain-model.md e docs/api-contract.md.
Crie listagem, formulário e detalhe básico.
Não use any.
Inclua loading, erro e estado vazio.
Não altere componentes compartilhados sem necessidade.
```

## 4. Cálculo de exposição

```text
Implemente ExposureCalculator no backend.
Considere apenas recebíveis OPEN, PARTIALLY_PAID e OVERDUE.
Não use float.
Adicione testes para títulos pagos, cancelados, parciais e vencidos.
```

## 5. Limite de crédito

```text
Implemente SetCreditLimit.
Impeça sobreposição de períodos ativos.
Registre auditoria.
Respeite organization_id do contexto autenticado.
Adicione testes unitários e de integração.
```

## 6. Importação

```text
Implemente o fluxo inicial de importação de recebíveis CSV:
upload, mapeamento, validação, preview e processamento.
O processamento deve ser idempotente.
Erros devem ser associados à linha.
Não implemente fila externa nesta etapa.
```

## 7. Revisão

```text
Revise esta alteração procurando:
vazamento entre tenants,
uso de float em valores monetários,
regras em controllers,
ausência de testes,
quebras de contrato da API,
efeitos colaterais em arquivos não relacionados.
Não altere o código; entregue primeiro o relatório.
```
