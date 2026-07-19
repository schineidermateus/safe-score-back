# Stone Traceability — Coding Guidelines

## Princípios

- PHP 8.4, Symfony, Doctrine ORM, MySQL, PHPUnit e PHPStan.
- Regras de negócio ficam no domínio ou em application services, nunca em controllers.
- DTOs separam contratos HTTP das entidades.
- Repositories de negócio recebem a organização explicitamente.
- `organization_id` vem exclusivamente do contexto autenticado e não integra payloads comuns.
- Associações entre entidades de negócio validam que ambos os lados pertencem ao mesmo tenant.
- Autorização é feita por capabilities; nomes de roles não concedem acesso.
- Operações críticas são transacionais e auditáveis.

## Estrutura modular

```text
Module/
  Domain/
  Application/
  Infrastructure/
  Presentation/
```

## Identificadores

- PKs são `INT UNSIGNED AUTO_INCREMENT`, ou `BIGINT` somente com justificativa de capacidade.
- PHP e JSON tratam IDs como inteiros numéricos.
- FKs usam o mesmo tipo da PK referenciada.
- UUID, ULID, GUID e IDs string não são permitidos.

## Dinheiro e medidas

- Persistir dinheiro e medidas como `DECIMAL` com precisão, escala e unidade explícitas.
- Dinheiro é serializado como string decimal.
- Nunca usar `float` para cálculo de domínio.
- Arredondamento e unidade canônica devem ser documentados por feature.

## APIs

- Controllers validam DTOs, chamam um caso de uso e transformam a resposta.
- Parâmetros de ID em rota aceitam somente dígitos.
- IDs de outro tenant não revelam a existência do recurso.
- Mudanças de estado relevantes usam ações explícitas.

## Testes e Definition of Done

Toda alteração deve cobrir, conforme o risco: domínio, repository, API, capabilities, tenant, migrations, fixtures e auditoria. O container, mapping Doctrine, PHPStan, PHPUnit e coding standards devem passar. Features sem spec suficiente não devem ganhar entidades ou APIs reservadas.
