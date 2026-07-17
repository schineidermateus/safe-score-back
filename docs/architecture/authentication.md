# Autenticação

Em produção, a API recebe um access token JWT no header `Authorization: Bearer`. A
assinatura é validada com uma chave pública obtida por HTTPS no endpoint JWKS do
serviço de identidade.

## Claims obrigatórios

- `iss`: emissor configurado em `JWT_ISSUER`;
- `sub`: identificador estável do usuário no emissor;
- `aud`: deve incluir o valor de `JWT_AUDIENCE`;
- `exp`: expiração em timestamp inteiro;
- `email`: endereço válido usado apenas como dado de perfil;
- `organization_id`: ID inteiro positivo da organização atual.

A identidade local é resolvida pelo par imutável `iss + sub`. O e-mail não deve ser
usado como chave de autenticação porque pode mudar. Os papéis do token também não
concedem permissões: autorização e roles são obtidas da membership persistida no
banco.

## Validações de acesso

Após validar assinatura e claims, o backend exige:

1. usuário local ativo e vinculado ao mesmo `iss + sub`;
2. organização indicada pelo token ativa;
3. membership ativa entre o usuário e a organização;
4. capability necessária para a operação.

Conhecer um ID sequencial ou enviar o `organization_id` de outro tenant não concede
acesso.

## JWKS

Somente chaves RSA destinadas à verificação de assinaturas RS256 são aceitas. O
documento é mantido em `cache.app`; um `kid` desconhecido permite atualização
controlada para suportar rotação de chaves, com limitação de frequência para evitar
requisições externas a cada token inválido.

Variáveis relevantes:

```env
JWKS_URI=https://auth.safescore.local/v1/jwks
JWKS_CACHE_TTL=3600
JWKS_REFRESH_INTERVAL=30
JWT_ISSUER=https://auth.safescore.local
JWT_AUDIENCE=safescore-api
JWT_CLOCK_SKEW=30
```

O `JWKS_URI` de produção deve usar HTTPS. Usuários provisionados localmente precisam
ter `identity_issuer` e `external_subject` vinculados antes de conseguirem autenticar.
Os providers baseados em `DEV_USER_ID` e `DEV_ORGANIZATION_ID` permanecem restritos
aos ambientes de desenvolvimento e teste.
