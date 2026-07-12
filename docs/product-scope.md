# SafeScore — Product Scope

## 1. Visão do produto

O SafeScore é uma plataforma SaaS B2B de inteligência financeira voltada para empresas que vendem a prazo.

Seu objetivo inicial é ajudar equipes financeiras a controlar limite de crédito, exposição financeira, recebíveis em aberto, atrasos e concentração de carteira.

O SafeScore não substitui o ERP. Ele funciona como uma camada complementar de análise e decisão, recebendo dados do sistema de origem por importação CSV e, futuramente, por integrações.

## 2. Problema principal

Muitas empresas B2B possuem informações de crédito, clientes e recebíveis distribuídas entre ERP, planilhas, e-mails e conhecimento informal da equipe.

Isso dificulta responder rapidamente:

- quanto cada cliente deve;
- quanto do limite já foi utilizado;
- quais clientes estão acima do limite;
- quais clientes concentram a maior exposição;
- quais títulos estão vencidos;
- quais clientes exigem atenção imediata.

## 3. Hipótese de produto

Empresas B2B que vendem a prazo e controlam risco de crédito de forma manual ou insuficiente estão dispostas a pagar por uma solução que centralize limites, recebíveis, exposição e alertas.

## 4. Cliente ideal inicial

- Empresas B2B com vendas recorrentes a prazo.
- Faturamento anual aproximado entre R$ 5 milhões e R$ 100 milhões.
- Equipe financeira entre 2 e 20 pessoas.
- Utilização de ERP combinada com planilhas paralelas.
- Carteira com pelo menos 50 clientes ativos.
- Segmentos iniciais: indústrias, distribuidoras, atacadistas, transportadoras e rochas ornamentais.

## 5. Usuários do sistema

### Administrador
Configura a organização, usuários e permissões.

### Gestor financeiro
Acompanha indicadores, aprova limites e analisa clientes críticos.

### Analista de crédito
Cadastra limites, importa dados, acompanha alertas e revisa clientes.

### Consulta
Acessa dashboards e dados sem poder alterar informações sensíveis.

## 6. Proposta de valor

O SafeScore deve permitir que um gestor financeiro identifique, em poucos minutos, os clientes com maior risco operacional de crédito com base em dados objetivos.

## 7. Momento principal de valor

```text
Importar clientes, limites e recebíveis
→ calcular exposição
→ comparar exposição com limite
→ classificar situações críticas
→ exibir alertas e indicadores
```

## 8. Escopo do MVP

- Autenticação.
- Organização e usuários.
- Cadastro e consulta de clientes.
- Histórico de limites de crédito.
- Cadastro e importação de recebíveis.
- Importação de CSV.
- Cálculo de exposição.
- Cálculo de crédito disponível.
- Aging de recebíveis.
- Alertas baseados em regras.
- Dashboard executivo.
- Auditoria básica.

## 9. Fora do escopo do MVP

- Inteligência artificial generativa.
- Machine learning.
- Previsão probabilística de inadimplência.
- Consulta automática a bureaus de crédito.
- Open Finance.
- Emissão de boletos.
- Cobrança automatizada.
- Antecipação de recebíveis.
- Escrow.
- Integrações bancárias.
- Integrações diretas com ERPs.
- Aplicativo mobile.
- Marketplace de crédito.

## 10. Indicadores iniciais de sucesso

- Organizações que concluem uma importação.
- Usuários que acessam o dashboard semanalmente.
- Clientes analisados por organização.
- Alertas visualizados ou resolvidos.
- Limites revisados.
- Tempo entre cadastro e primeira análise útil.
- Retenção semanal dos usuários financeiros.

## 11. Princípios do produto

1. Decisão antes de visualização.
2. Dados rastreáveis.
3. Regras explicáveis.
4. Importação simples.
5. Segurança multiempresa.
6. Poucas telas e alto valor.
7. Nenhuma promessa de score preditivo sem dados suficientes.
