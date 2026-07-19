# Relatório de implementação da migration

## Escopo implementado

- remoção do domínio financeiro e das cinco migrations SafeScore;
- baseline modular para banco novo;
- authorization por capabilities persistidas;
- manutenção da função administrativa de membership sem concessão implícita de acesso;
- imports genéricos preservados, com tipos industriais ainda não operacionais rejeitados explicitamente;
- auditoria com correlation ID opcional;
- fundação industrial: BusinessPartner, Material, Quarry, StorageLocation e Machine;
- fixtures para dois tenants sem IDs fixos.

## Fora do escopo

Blocks, ordens de produção, chapas, lotes, estoque, rendimento, custos e pricing dependem de specs próprias.

## Ambiente de validação

Os comandos PHP usam `.tools/php/php.exe`. A máquina não dispõe de infraestrutura para subir containers; migrations e fixtures exigem uma instância MySQL de teste identificada antes da validação destrutiva.
