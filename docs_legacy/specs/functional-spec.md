# SafeScore MVP — Functional Specification

## FR-001 Autenticação

O usuário deve poder entrar com e-mail e senha.

### Aceite
- credenciais válidas iniciam sessão;
- credenciais inválidas retornam mensagem genérica;
- rotas privadas exigem autenticação.

## FR-002 Organização ativa

Todo usuário deve operar dentro de uma organização.

### Aceite
- dados são filtrados pela organização;
- usuário sem vínculo não acessa dados;
- nenhum endpoint aceita tenant arbitrário.

## FR-003 Cadastro de cliente

Permitir criar, editar, listar e consultar clientes.

### Campos mínimos
- razão social;
- nome fantasia;
- documento;
- identificador externo;
- status.

## FR-004 Limite de crédito

Permitir definir limite com vigência e justificativa.

### Aceite
- histórico preservado;
- não há sobreposição de vigência;
- alteração gera auditoria;
- limite vigente aparece no resumo do cliente.

## FR-005 Recebíveis

Permitir cadastrar e consultar títulos.

### Aceite
- saldo aberto não excede valor original;
- status é recalculado conforme vencimento;
- títulos pagos não entram na exposição.

## FR-006 Importação CSV

Permitir upload, mapeamento, validação, preview e processamento.

### Aceite
- erros são exibidos por linha;
- arquivo não é processado antes da confirmação;
- reprocessamento não duplica dados;
- resumo mostra sucessos e falhas.

## FR-007 Exposição

Calcular soma do saldo aberto dos recebíveis elegíveis.

## FR-008 Crédito disponível

Calcular limite vigente menos exposição.

## FR-009 Aging

Agrupar saldo aberto por faixa de vencimento.

## FR-010 Alertas

Gerar alertas de:

- 80% do limite;
- 100% do limite;
- exposição sem limite;
- título vencido;
- atraso acima de 30 dias;
- concentração elevada.

## FR-011 Dashboard

Exibir:

- exposição total;
- carteira vencida;
- percentual vencido;
- aging;
- top clientes por exposição;
- clientes acima do limite;
- alertas críticos.

## FR-012 Detalhe do cliente

Exibir:

- dados cadastrais;
- limite vigente;
- exposição;
- crédito disponível;
- utilização;
- títulos;
- aging;
- alertas;
- histórico de limites.

## FR-013 Auditoria

Registrar mudanças financeiras e administrativas relevantes.
