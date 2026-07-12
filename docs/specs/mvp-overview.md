# SafeScore MVP — Overview

## Objetivo

Permitir que uma organização importe dados de clientes, limites e recebíveis e identifique quais clientes exigem atenção financeira.

## Fluxo principal

```text
Criar organização
→ importar clientes
→ importar limites
→ importar recebíveis
→ calcular exposição
→ gerar alertas
→ analisar dashboard
→ abrir detalhe do cliente
```

## Personas

- Administrador.
- Gestor financeiro.
- Analista de crédito.
- Usuário de consulta.

## Épicos

1. Autenticação e organização.
2. Clientes.
3. Limites.
4. Recebíveis.
5. Importações.
6. Motor de indicadores.
7. Alertas.
8. Dashboard.
9. Auditoria.

## Critério de sucesso funcional

Com três arquivos CSV válidos, o usuário deve obter em até cinco minutos:

- exposição total;
- valor vencido;
- aging;
- clientes acima do limite;
- concentração por cliente;
- alertas críticos.
