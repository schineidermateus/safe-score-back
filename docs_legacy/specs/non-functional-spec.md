# SafeScore MVP — Non-Functional Specification

## Segurança

- HTTPS em produção.
- Senhas com algoritmo moderno.
- Rate limit em autenticação.
- Controle por função.
- Isolamento multiempresa.
- Upload com validação de tipo e tamanho.
- Segredos fora do repositório.

## Performance

Metas iniciais:

- dashboard em até 3 segundos para 10 mil recebíveis;
- listagens paginadas;
- importação de 20 mil linhas sem bloquear a interface;
- consultas com índices por organization_id, customer_id, status e due_date.

## Confiabilidade

- operações de importação idempotentes;
- transações em alterações críticas;
- logs estruturados;
- tratamento de falhas parciais em importação.

## Usabilidade

- interface responsiva para desktop;
- estados de loading, erro e vazio;
- mensagens de validação objetivas;
- valores monetários no padrão pt-BR;
- datas no padrão local.

## Observabilidade

- logs de aplicação;
- rastreamento de erro;
- métricas de importação;
- identificação de request;
- auditoria separada de log técnico.

## Privacidade

- minimizar dados pessoais;
- permitir exclusão ou anonimização quando aplicável;
- registrar acessos administrativos;
- documentar finalidade de tratamento.

## Compatibilidade

- Chrome e Edge atuais.
- Layout otimizado para 1366×768 ou superior.
