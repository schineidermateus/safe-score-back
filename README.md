# Stone Traceability Backend

Backend multi-tenant para a fundação de rastreabilidade de rochas ornamentais, desenvolvido em Symfony, Doctrine ORM e MySQL.

O produto usa uma baseline nova e não suporta a sequência de migrations do SafeScore. Todos os identificadores persistidos são inteiros gerados pelo banco. A fundação atual contém identidade, organizações, autorização por capabilities, imports genéricos, auditoria e cadastros industriais iniciais.

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

## Documentação da API

Com a infraestrutura local em execução, a documentação OpenAPI pode ser visualizada pela interface Swagger UI:

- Ambiente local: <http://localhost:8000/api/docs>

Para que esses endereços sejam resolvidos localmente, adicione as entradas abaixo ao arquivo `hosts` do sistema operacional:

```text
127.0.0.1 app.safescore.local
127.0.0.1 api.safescore.local
```

No Windows, o arquivo está em `C:\Windows\System32\drivers\etc\hosts` e deve ser editado como administrador. No Linux e macOS, edite `/etc/hosts` com privilégios administrativos.

Se `APP_HTTP_PORT` no arquivo `../safe-score-infra/.env` for diferente de `80`, informe a porta na URL, por exemplo `http://api.safescore.local:8080/api/docs`.

O contrato OpenAPI em JSON está disponível em <http://api.safescore.local/api/docs.jsonopenapi>. Também é possível exportá-lo pelo console:

```bash
make console cmd="api:openapi:export"
make console cmd="api:openapi:export --yaml"
```

Testes e qualidade:

```bash
make test
make test-file file="tests/Industrial/Domain/IndustrialFoundationTest.php"
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

Documentação funcional e técnica adicional está disponível em [`docs/README-DOCS.md`](docs/README-DOCS.md).
