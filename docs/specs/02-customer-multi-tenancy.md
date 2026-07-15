# Spec 02 — Customer Multi-Tenancy

## Objetivo
Adaptar Customer existente ao isolamento por organização.

## Analisar antes
ID atual, migration, repositories, DTOs, endpoints, fixtures e testes.

## Escopo
Customer com ID inteiro autoincremento, organization obrigatório, unicidade por tenant e consultas escopadas.

## Aceite
Sem vazamento entre tenants e migration segura.
