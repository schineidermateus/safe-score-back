<?php

declare(strict_types=1);

namespace App\Customers\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final readonly class CustomerOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    /** @param array<string, mixed> $context */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        $paths->addPath('/api/v1/customers', new PathItem(
            get: new Operation(
                operationId: 'customers_list',
                tags: ['Customers'],
                responses: [
                    '200' => new Response('Lista paginada de clientes da organização atual.', $this->jsonContent($this->listEnvelopeSchema())),
                    '403' => new Response('Membership ativa e permissão de leitura são obrigatórias.'),
                    '422' => new Response('Filtros ou paginação inválidos.'),
                ],
                summary: 'Lista clientes da organização atual',
                description: 'O tenant é obtido do contexto autenticado. organization_id não é aceito como filtro.',
                parameters: $this->listParameters(),
            ),
            post: new Operation(
                operationId: 'customers_create',
                tags: ['Customers'],
                responses: [
                    '201' => new Response('Cliente criado.', $this->jsonContent($this->customerEnvelopeSchema())),
                    '403' => new Response('Acesso negado. VIEWER e memberships inativas não podem criar.'),
                    '409' => new Response('Documento já utilizado na organização atual.'),
                    '422' => new Response('Payload inválido.'),
                ],
                summary: 'Cria um cliente na organização atual',
                description: 'A organização vem exclusivamente do contexto atual; organization_id enviado pelo cliente é rejeitado.',
                requestBody: $this->customerRequestBody(false),
            ),
        ));

        $paths->addPath('/api/v1/customers/{id}', new PathItem(
            get: new Operation(
                operationId: 'customers_get',
                tags: ['Customers'],
                responses: [
                    '200' => new Response('Cliente encontrado.', $this->jsonContent($this->customerEnvelopeSchema())),
                    '403' => new Response('Acesso negado.'),
                    '404' => new Response('Cliente inexistente ou pertencente a outra organização.'),
                ],
                summary: 'Consulta um cliente da organização atual',
            ),
            delete: new Operation(
                operationId: 'customers_delete',
                tags: ['Customers'],
                responses: [
                    '204' => new Response('Cliente excluído logicamente.'),
                    '403' => new Response('Acesso negado.'),
                    '404' => new Response('Cliente inexistente ou pertencente a outra organização.'),
                ],
                summary: 'Exclui logicamente um cliente da organização atual',
            ),
            patch: new Operation(
                operationId: 'customers_update',
                tags: ['Customers'],
                responses: [
                    '200' => new Response('Cliente atualizado.', $this->jsonContent($this->customerEnvelopeSchema())),
                    '403' => new Response('Acesso negado.'),
                    '404' => new Response('Cliente inexistente ou pertencente a outra organização.'),
                    '409' => new Response('Documento já utilizado na organização atual.'),
                    '422' => new Response('Payload inválido.'),
                ],
                summary: 'Atualiza um cliente da organização atual',
                description: 'A organização do cliente não pode ser alterada.',
                requestBody: $this->customerRequestBody(true),
            ),
            parameters: [new Parameter(
                name: 'id',
                in: 'path',
                description: 'Identificador inteiro do cliente.',
                required: true,
                schema: ['type' => 'integer', 'format' => 'int32', 'minimum' => 1],
                example: 123,
            )],
        ));

        return $openApi;
    }

    /** @return list<Parameter> */
    private function listParameters(): array
    {
        return [
            new Parameter('search', 'query', 'Busca por nome, documento ou identificador externo.', schema: ['type' => 'string']),
            new Parameter('status', 'query', 'Filtra por status.', schema: ['type' => 'string', 'enum' => ['ACTIVE', 'INACTIVE']]),
            new Parameter('page', 'query', 'Página, iniciando em 1.', schema: ['type' => 'integer', 'minimum' => 1], example: 1),
            new Parameter('per_page', 'query', 'Itens por página.', schema: ['type' => 'integer', 'minimum' => 1, 'maximum' => 100], example: 20),
            new Parameter('sort', 'query', 'Ordenação.', schema: ['type' => 'string', 'enum' => ['legal_name', '-legal_name', 'created_at', '-created_at']]),
        ];
    }

    private function customerRequestBody(bool $includeStatus): RequestBody
    {
        $properties = [
            'legal_name' => ['type' => 'string', 'maxLength' => 180],
            'trade_name' => ['type' => ['string', 'null'], 'maxLength' => 180],
            'document' => ['type' => ['string', 'null'], 'maxLength' => 18],
            'external_id' => ['type' => ['string', 'null'], 'maxLength' => 100],
            'segment' => ['type' => ['string', 'null'], 'maxLength' => 100],
            'account_manager' => ['type' => ['string', 'null'], 'maxLength' => 120],
        ];
        if ($includeStatus) {
            $properties['status'] = ['type' => 'string', 'enum' => ['ACTIVE', 'INACTIVE']];
        }

        return new RequestBody(
            description: 'organization_id não faz parte do contrato de entrada.',
            content: $this->jsonContent([
                'type' => 'object',
                'required' => ['legal_name'],
                'additionalProperties' => false,
                'properties' => $properties,
            ], [
                'legal_name' => 'Cliente Exemplo LTDA',
                'trade_name' => 'Cliente Exemplo',
                'document' => '04.252.011/0001-10',
                'external_id' => 'ERP-123',
                ...($includeStatus ? ['status' => 'ACTIVE'] : []),
            ]),
            required: true,
        );
    }

    /** @return array<string, mixed> */
    private function customerSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'legal_name', 'status', 'created_at', 'updated_at'],
            'properties' => [
                'id' => ['type' => 'integer', 'format' => 'int32', 'example' => 123],
                'external_id' => ['type' => ['string', 'null']],
                'legal_name' => ['type' => 'string'],
                'trade_name' => ['type' => ['string', 'null']],
                'document' => ['type' => ['string', 'null']],
                'segment' => ['type' => ['string', 'null']],
                'status' => ['type' => 'string', 'enum' => ['ACTIVE', 'INACTIVE']],
                'account_manager' => ['type' => ['string', 'null']],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function customerEnvelopeSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => $this->customerSchema(),
                'meta' => ['type' => 'object'],
                'errors' => ['type' => 'array', 'items' => ['type' => 'object']],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function listEnvelopeSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => ['type' => 'array', 'items' => $this->customerSchema()],
                'meta' => [
                    'type' => 'object',
                    'properties' => [
                        'page' => ['type' => 'integer'],
                        'per_page' => ['type' => 'integer'],
                        'total' => ['type' => 'integer'],
                        'total_pages' => ['type' => 'integer'],
                    ],
                ],
                'errors' => ['type' => 'array', 'items' => ['type' => 'object']],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return \ArrayObject<string, MediaType>
     */
    private function jsonContent(array $schema, mixed $example = null): \ArrayObject
    {
        return new \ArrayObject([
            'application/json' => new MediaType(new \ArrayObject($schema), $example),
        ]);
    }
}
