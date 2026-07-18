# Backend Docs

Copie a pasta `docs` para a raiz de `safe-score-back`.

## Arquitetura

- [Autenticação](architecture/authentication.md)
- [Autorização](architecture/authorization.md)
- [Multi-tenancy](architecture/multi-tenancy.md)

Fluxo recomendado por spec:

1. diagnóstico sem alterações;
2. revisão humana;
3. implementação de uma única spec;
4. revisão técnica;
5. testes e merge.
