# SafeScore Backend

Backend do SafeScore desenvolvido em Symfony, Doctrine ORM e MySQL. O ambiente local integrado é fornecido pelo repositório irmão `../safe-score-infra`.

## Preparação do ambiente

Antes dos comandos do backend, configure a infraestrutura:

```bash
cp ../safe-score-infra/.env.example ../safe-score-infra/.env
```

Revise as variáveis e credenciais locais no novo arquivo `.env`. O `Makefile` utiliza os services reais `backend` e `mysql` definidos pelo Docker Compose da infraestrutura.

## Comandos de desenvolvimento

Use o help para consultar todos os targets e parâmetros disponíveis:

```bash
make help
make env-check
```

Fluxo inicial mais comum:

```bash
make up
make install
make migrate
```

Testes e qualidade:

```bash
make test
make test-file file="tests/Customers/Application/UseCase/CustomerUseCasesTest.php"
make phpstan
make cs-check
make quality
```

Console Symfony e banco:

```bash
make console cmd="debug:container"
make migration
make migrate-status
make schema-validate
```

Operações destrutivas exigem confirmação explícita:

```bash
make db-drop confirm=YES
make migrate-prev confirm=YES
make fixtures confirm=YES
make test-db-reset confirm=YES
```

A configuração atual do PHPUnit possui somente a suíte `Project Test Suite`, abrangendo todo o diretório `tests`. Por isso não existem targets artificiais separados para testes unitários e de integração. O target `coverage` depende de um driver de cobertura como Xdebug ou PCOV, que não está instalado na imagem atual.

O Doctrine gera nomes de migrations baseados em timestamp; o comando `make migration` não aceita um parâmetro `name` porque a versão instalada não oferece essa opção de forma nativa.

Documentação funcional e técnica adicional está disponível em [`docs/README.md`](docs/README.md).
