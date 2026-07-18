<?php

declare(strict_types=1);

namespace App\Credit\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final readonly class CreditLimitOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    /** @param array<string, mixed> $context */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        $paths->addPath('/api/v1/customers/{customerId}/credit-limits', new PathItem(
            get: new Operation(
                operationId: 'credit_limits_history',
                tags: ['Credit Limits'],
                responses: $this->responses('Histórico paginado de limites.', $this->listEnvelopeSchema()),
                summary: 'Lista o histórico de limites do cliente',
                parameters: [
                    ...$this->customerParameters(),
                    new Parameter('page', 'query', 'Página, iniciando em 1.', schema: ['type' => 'integer', 'minimum' => 1]),
                    new Parameter('per_page', 'query', 'Itens por página.', schema: ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]),
                ],
            ),
            post: new Operation(
                operationId: 'credit_limits_create',
                tags: ['Credit Limits'],
                responses: $this->responses('Limite ativo criado.', $this->envelopeSchema(), 201),
                summary: 'Cria um limite ativo para o cliente',
                description: 'A organização e o aprovador são obtidos do contexto autenticado. Períodos ativos não podem se sobrepor.',
                requestBody: $this->limitRequestBody(),
                parameters: $this->customerParameters(),
            ),
        ));

        $paths->addPath('/api/v1/customers/{customerId}/credit-limits/active', new PathItem(
            get: new Operation(
                operationId: 'credit_limits_active',
                tags: ['Credit Limits'],
                responses: $this->responses('Limite aplicável na data ou data nula quando inexistente.', $this->nullableEnvelopeSchema()),
                summary: 'Resolve o limite ativo em uma data',
                parameters: [
                    ...$this->customerParameters(),
                    new Parameter('reference_date', 'query', 'Data de referência inclusiva.', required: true, schema: ['type' => 'string', 'format' => 'date'], example: '2026-07-15'),
                ],
            ),
        ));

        $paths->addPath('/api/v1/credit-limits/{id}', new PathItem(
            get: new Operation(
                operationId: 'credit_limits_get',
                tags: ['Credit Limits'],
                responses: $this->responses('Limite encontrado.', $this->envelopeSchema()),
                summary: 'Consulta um limite da organização atual',
            ),
            patch: new Operation(
                operationId: 'credit_limits_update',
                tags: ['Credit Limits'],
                responses: $this->responses('Limite atualizado.', $this->envelopeSchema()),
                summary: 'Atualiza um limite editável',
                requestBody: $this->limitRequestBody(),
            ),
            parameters: $this->idParameters(),
        ));

        $paths->addPath('/api/v1/credit-limits/{id}/revoke', new PathItem(
            post: new Operation(
                operationId: 'credit_limits_revoke',
                tags: ['Credit Limits'],
                responses: $this->responses('Limite revogado.', $this->envelopeSchema()),
                summary: 'Revoga um limite',
                requestBody: new RequestBody(
                    content: $this->jsonContent([
                        'type' => 'object',
                        'required' => ['reason'],
                        'additionalProperties' => false,
                        'properties' => ['reason' => ['type' => 'string', 'maxLength' => 1000]],
                    ]),
                    required: true,
                ),
            ),
            parameters: $this->idParameters(),
        ));

        return $openApi;
    }

    private function limitRequestBody(): RequestBody
    {
        return new RequestBody(
            description: 'Valores monetários são strings decimais; floats não fazem parte do contrato.',
            content: $this->jsonContent([
                'type' => 'object',
                'required' => ['amount', 'valid_from', 'reason'],
                'additionalProperties' => false,
                'properties' => [
                    'amount' => ['type' => 'string', 'pattern' => '^(?:0|[1-9]\\d{0,16})(?:\\.\\d{1,2})?$', 'example' => '150000.00'],
                    'valid_from' => ['type' => 'string', 'format' => 'date'],
                    'valid_until' => ['type' => ['string', 'null'], 'format' => 'date'],
                    'reason' => ['type' => 'string', 'maxLength' => 1000],
                ],
            ]),
            required: true,
        );
    }

    /**
     * @param array<string, mixed> $successSchema
     *
     * @return array<int|string, Response>
     */
    private function responses(string $successDescription, array $successSchema, int $successStatus = 200): array
    {
        return [
            (string) $successStatus => new Response($successDescription, $this->jsonContent($successSchema)),
            '403' => new Response('Membership inativa ou permissão insuficiente.'),
            '404' => new Response('Cliente ou limite inexistente na organização atual.'),
            '409' => new Response('Conflito de período ou estado.'),
            '422' => new Response('Payload, valor ou vigência inválidos.'),
        ];
    }

    /** @return list<Parameter> */
    private function customerParameters(): array
    {
        return [new Parameter('customerId', 'path', 'Identificador inteiro do cliente.', required: true, schema: ['type' => 'integer', 'format' => 'int32', 'minimum' => 1])];
    }

    /** @return list<Parameter> */
    private function idParameters(): array
    {
        return [new Parameter('id', 'path', 'Identificador inteiro do limite.', required: true, schema: ['type' => 'integer', 'format' => 'int32', 'minimum' => 1])];
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'customer_id', 'amount', 'valid_from', 'status', 'reason'],
            'properties' => [
                'id' => ['type' => 'integer', 'format' => 'int32'],
                'customer_id' => ['type' => 'integer', 'format' => 'int32'],
                'amount' => ['type' => 'string', 'pattern' => '^\\d+\\.\\d{2}$', 'example' => '150000.00'],
                'valid_from' => ['type' => 'string', 'format' => 'date'],
                'valid_until' => ['type' => ['string', 'null'], 'format' => 'date'],
                'status' => ['type' => 'string', 'enum' => ['DRAFT', 'ACTIVE', 'EXPIRED', 'REVOKED']],
                'reason' => ['type' => 'string'],
                'approved_by_user_id' => ['type' => ['integer', 'null'], 'format' => 'int32'],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function envelopeSchema(): array
    {
        return ['type' => 'object', 'properties' => ['data' => $this->schema(), 'meta' => ['type' => 'object'], 'errors' => ['type' => 'array']]];
    }

    /** @return array<string, mixed> */
    private function nullableEnvelopeSchema(): array
    {
        $schema = $this->envelopeSchema();
        $schema['properties']['data'] = ['oneOf' => [$this->schema(), ['type' => 'null']]];

        return $schema;
    }

    /** @return array<string, mixed> */
    private function listEnvelopeSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => ['type' => 'array', 'items' => $this->schema()],
                'meta' => ['type' => 'object', 'properties' => [
                    'page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                ]],
                'errors' => ['type' => 'array'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return \ArrayObject<string, MediaType>
     */
    private function jsonContent(array $schema): \ArrayObject
    {
        return new \ArrayObject(['application/json' => new MediaType(new \ArrayObject($schema))]);
    }
}
