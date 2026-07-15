<?php

declare(strict_types=1);

namespace App\Receivables\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final readonly class ReceivableOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    /** @param array<string, mixed> $context */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();
        $paths->addPath('/api/v1/receivables', new PathItem(
            get: new Operation(operationId: 'receivables_list', tags: ['Receivables'], responses: $this->responses('Recebíveis paginados.'), summary: 'Lista recebíveis do tenant', parameters: $this->filters()),
            post: new Operation(operationId: 'receivables_create', tags: ['Receivables'], responses: $this->responses('Recebível criado.', 201), summary: 'Cria recebível manual', description: 'organization_id e source não são aceitos. Valores monetários são strings decimais.', requestBody: $this->request($this->writeSchema(true))),
        ));
        $paths->addPath('/api/v1/receivables/{id}', new PathItem(
            get: new Operation(operationId: 'receivables_get', tags: ['Receivables'], responses: $this->responses('Recebível e histórico de pagamentos.'), summary: 'Consulta recebível', parameters: [new Parameter('reference_date', 'query', schema: ['type' => 'string', 'format' => 'date'])]),
            patch: new Operation(operationId: 'receivables_update', tags: ['Receivables'], responses: $this->responses('Recebível atualizado.'), summary: 'Atualiza campos editáveis', requestBody: $this->request($this->writeSchema(false))),
            parameters: $this->idParameter(),
        ));
        $paths->addPath('/api/v1/receivables/{id}/payments', new PathItem(post: new Operation(operationId: 'receivables_payment_register', tags: ['Receivables'], responses: $this->responses('Pagamento registrado.', 201), summary: 'Registra pagamento imutável', requestBody: $this->request(['type' => 'object', 'required' => ['amount', 'payment_date'], 'additionalProperties' => false, 'properties' => ['amount' => $this->positiveMoney(), 'payment_date' => ['type' => 'string', 'format' => 'date']]])), parameters: $this->idParameter()));
        $paths->addPath('/api/v1/receivables/{id}/cancel', new PathItem(post: new Operation(operationId: 'receivables_cancel', tags: ['Receivables'], responses: $this->responses('Recebível cancelado.'), summary: 'Cancela sem excluir o histórico', requestBody: $this->request(['type' => 'object', 'required' => ['reason'], 'additionalProperties' => false, 'properties' => ['reason' => ['type' => 'string', 'maxLength' => 1000]]])), parameters: $this->idParameter()));

        return $openApi;
    }

    /** @return list<Parameter> */
    private function filters(): array
    {
        $parameters = [];
        foreach (['customer_id', 'status', 'overdue', 'due_date_from', 'due_date_to', 'aging_bucket', 'amount_min', 'amount_max', 'search', 'reference_date', 'page', 'per_page', 'sort'] as $name) {
            $parameters[] = new Parameter($name, 'query', schema: ['type' => in_array($name, ['customer_id', 'page', 'per_page'], true) ? 'integer' : ('overdue' === $name ? 'boolean' : 'string')]);
        }

        return $parameters;
    }

    /** @return list<Parameter> */
    private function idParameter(): array
    {
        return [new Parameter('id', 'path', required: true, schema: ['type' => 'integer', 'format' => 'int32', 'minimum' => 1])];
    }

    /** @return array<string, mixed> */
    private function writeSchema(bool $create): array
    {
        $required = ['document_number', 'issue_date', 'due_date', 'original_amount'];
        if ($create) {
            array_unshift($required, 'customer_id');
        }
        $properties = ['document_number' => ['type' => 'string', 'maxLength' => 100], 'issue_date' => ['type' => 'string', 'format' => 'date'], 'due_date' => ['type' => 'string', 'format' => 'date'], 'original_amount' => $this->money()];
        if ($create) {
            $properties = ['customer_id' => ['type' => 'integer', 'format' => 'int32'], 'external_id' => ['type' => ['string', 'null'], 'maxLength' => 150], ...$properties];
        }

        return ['type' => 'object', 'required' => $required, 'additionalProperties' => false, 'properties' => $properties];
    }

    /** @return array<string, mixed> */
    private function money(): array
    {
        return ['type' => 'string', 'pattern' => '^(?:0|[1-9]\\d{0,16})(?:\\.\\d{1,2})?$', 'example' => '1250.00'];
    }

    /** @return array<string, mixed> */
    private function positiveMoney(): array
    {
        return ['type' => 'string', 'pattern' => '^(?:(?:[1-9]\\d{0,16})(?:\\.\\d{1,2})?|0\\.(?:0[1-9]|[1-9]\\d))$', 'example' => '250.00'];
    }

    /** @param array<string, mixed> $schema */
    private function request(array $schema): RequestBody
    {
        return new RequestBody(content: $this->content($schema), required: true);
    }

    /** @return array<int|string, Response> */
    private function responses(string $description, int $status = 200): array
    {
        return [(string) $status => new Response($description, $this->content($this->envelopeSchema())), '403' => new Response('Permissão insuficiente ou membership inativa.'), '404' => new Response('Recurso não encontrado no tenant.'), '409' => new Response('Conflito de estado, saldo ou idempotência.'), '422' => new Response('Dados inválidos.')];
    }

    /** @return array<string, mixed> */
    private function envelopeSchema(): array
    {
        $receivable = ['type' => 'object', 'required' => ['id', 'customer_id', 'original_amount', 'open_amount', 'paid_amount', 'status'], 'properties' => [
            'id' => ['type' => 'integer', 'format' => 'int32'], 'customer_id' => ['type' => 'integer', 'format' => 'int32'],
            'source' => ['type' => 'string'], 'external_id' => ['type' => ['string', 'null']], 'document_number' => ['type' => 'string'],
            'issue_date' => ['type' => 'string', 'format' => 'date'], 'due_date' => ['type' => 'string', 'format' => 'date'],
            'original_amount' => $this->money(), 'open_amount' => $this->money(), 'paid_amount' => $this->money(), 'currency' => ['type' => 'string'],
            'payment_date' => ['type' => ['string', 'null'], 'format' => 'date'], 'status' => ['type' => 'string', 'enum' => ['OPEN', 'PARTIALLY_PAID', 'PAID', 'OVERDUE', 'CANCELLED']],
            'aging_bucket' => ['type' => ['string', 'null']], 'payments' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'amount' => $this->money(), 'payment_date' => ['type' => 'string', 'format' => 'date']]]],
        ]];

        return ['type' => 'object', 'properties' => ['data' => ['oneOf' => [$receivable, ['type' => 'array', 'items' => $receivable]]], 'meta' => ['type' => 'object'], 'errors' => ['type' => 'array']]];
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return \ArrayObject<string, MediaType>
     */
    private function content(array $schema): \ArrayObject
    {
        return new \ArrayObject(['application/json' => new MediaType(new \ArrayObject($schema))]);
    }
}
