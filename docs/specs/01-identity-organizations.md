# Spec 01 — Identity and Organizations

## Objetivo
Implementar User, Organization, OrganizationMembership, roles fixas e providers temporários.

## Analisar antes
Inspecionar entidades, migrations, fixtures, serviços, testes e padrões existentes.

## Escopo
User, Organization, Membership, enums, `/api/v1/me`, membros, proteção do último OWNER e fixtures para dois tenants.

## Fora do escopo
OAuth, JWT, senha e convites.

## Aceite
IDs INT AUTO_INCREMENT, providers dev/test bloqueados em produção e testes passando.
