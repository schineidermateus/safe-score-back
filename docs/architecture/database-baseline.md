# Database baseline

O banco deve estar vazio antes da primeira execução. Não execute as migrations desta branch sobre um banco SafeScore.

A baseline é modular:

1. identity e organizations;
2. roles e capabilities;
3. imports e audit;
4. industrial foundation.

PKs e FKs usam `INT UNSIGNED`. Não são permitidos UUID, ULID ou identificadores string. Cadastros de negócio carregam `organization_id`; queries e associações devem ser validadas no tenant do contexto autenticado.
