# Relatório de implementação da migration

## Escopo implementado

- remoção do domínio financeiro e das cinco migrations SafeScore;
- baseline modular para banco novo;
- authorization por capabilities técnicas persistidas, limitada a imports, auditoria e administração de memberships;
- manutenção da função administrativa de membership sem concessão implícita de acesso;
- imports genéricos preservados, com tipos industriais ainda não operacionais rejeitados explicitamente;
- correlation ID validado ou gerado por requisição, devolvido no header HTTP e propagado automaticamente para a auditoria;
- fixtures para dois tenants sem IDs fixos.

## Fora do escopo

Business partners, materiais, pedreiras, locais de armazenamento, máquinas, blocks, ordens de produção, chapas, lotes, estoque, rendimento, custos e pricing dependem de specs próprias.

## Identificadores

Todas as PKs e FKs da baseline usam `BIGINT UNSIGNED`. IDs permanecem `int` no PHP e números no JSON. UUID, ULID, GUID e IDs string são proibidos.

## Ambiente de validação

Os comandos PHP usam `.tools/php/php.exe`. A máquina não dispõe de infraestrutura para subir containers.

Validações concluídas:

- Composer validate;
- cache Symfony em `test`;
- lint de YAML e container;
- oito mappings Doctrine;
- geração do SQL do schema para MySQL;
- PHPStan sem erros;
- PHPUnit com 74 testes e 501 assertions;
- coding standards nos arquivos alterados.

Validações bloqueadas pelo ambiente:

- criação do banco de teste;
- execução real das migrations;
- comparação do schema com MySQL;
- carregamento real das fixtures.

Os três comandos dependentes do banco falharam antes de executar SQL porque o hostname MySQL `database` não pôde ser resolvido. SQLite não foi utilizado como substituto.
