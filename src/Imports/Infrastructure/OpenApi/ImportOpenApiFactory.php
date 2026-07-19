<?php

declare(strict_types=1);

namespace App\Imports\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final readonly class ImportOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    /** @param array<string, mixed> $context */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();
        $paths->addPath('/api/v1/imports', new PathItem(
            get: new Operation(operationId: 'imports_list', tags: ['Imports'], responses: $this->responses('Lotes paginados.'), summary: 'Lista lotes do tenant', parameters: $this->pagination()),
            post: new Operation(operationId: 'imports_create', tags: ['Imports'], responses: $this->responses('Lote criado.', 201), summary: 'Faz upload de CSV', description: 'Os tipos industriais são reservados e retornam IMPORT_TYPE_NOT_IMPLEMENTED até a respectiva spec.', requestBody: new RequestBody(content: new \ArrayObject(['multipart/form-data' => new MediaType(new \ArrayObject(['type' => 'object', 'required' => ['type', 'file'], 'properties' => ['type' => ['type' => 'string', 'enum' => ['BUSINESS_PARTNERS', 'MATERIALS', 'QUARRIES', 'STORAGE_LOCATIONS', 'BLOCKS', 'SLABS', 'LOTS', 'INVENTORY_OPENING', 'PRODUCTION_COSTS']], 'file' => ['type' => 'string', 'format' => 'binary']]]))]), required: true)),
        ));
        $paths->addPath('/api/v1/imports/{id}', new PathItem(get: new Operation(operationId: 'imports_get', tags: ['Imports'], responses: $this->responses('Lote.'), summary: 'Consulta lote'), parameters: $this->id()));
        $mapping = ['type' => 'object', 'required' => ['mapping'], 'additionalProperties' => false, 'properties' => ['mapping' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']]]];
        $paths->addPath('/api/v1/imports/{id}/mapping', new PathItem(post: new Operation(operationId: 'imports_mapping', tags: ['Imports'], responses: $this->responses('Mapeamento salvo.'), summary: 'Configura mapeamento', requestBody: $this->jsonBody($mapping)), parameters: $this->id()));
        $paths->addPath('/api/v1/imports/{id}/validate', new PathItem(post: $this->post('imports_validate', 'Valida arquivo e persiste preview.'), parameters: $this->id()));
        $paths->addPath('/api/v1/imports/{id}/preview', new PathItem(get: new Operation(operationId: 'imports_preview', tags: ['Imports'], responses: $this->responses('Preview paginado.'), summary: 'Consulta preview', parameters: $this->pagination()), parameters: $this->id()));
        $paths->addPath('/api/v1/imports/{id}/process', new PathItem(post: $this->post('imports_process', 'Processa linhas válidas sincronicamente.'), parameters: $this->id()));
        $paths->addPath('/api/v1/imports/{id}/errors', new PathItem(get: new Operation(operationId: 'imports_errors', tags: ['Imports'], responses: $this->responses('Erros paginados.'), summary: 'Lista erros por linha', parameters: $this->pagination()), parameters: $this->id()));
        $paths->addPath('/api/v1/imports/{id}/cancel', new PathItem(post: $this->post('imports_cancel', 'Cancela lote não processado.'), parameters: $this->id()));

        return $openApi;
    }

    private function post(string $id, string $summary): Operation
    {
        return new Operation(operationId: $id, tags: ['Imports'], responses: $this->responses('Lote atualizado.'), summary: $summary);
    }

    /** @return list<Parameter> */
    private function id(): array
    {
        return [new Parameter('id', 'path', required: true, schema: ['type' => 'integer', 'format' => 'int32', 'minimum' => 1])];
    }

    /** @return list<Parameter> */
    private function pagination(): array
    {
        return [new Parameter('page', 'query', schema: ['type' => 'integer', 'minimum' => 1]), new Parameter('per_page', 'query', schema: ['type' => 'integer', 'minimum' => 1, 'maximum' => 100])];
    }

    /** @param array<string, mixed> $schema */
    private function jsonBody(array $schema): RequestBody
    {
        return new RequestBody(content: $this->content($schema), required: true);
    }

    /** @return array<int|string, Response> */
    private function responses(string $description, int $status = 200): array
    {
        return [(string) $status => new Response($description, $this->content($this->envelopeSchema())), '403' => new Response('Permissão insuficiente.'), '404' => new Response('Lote não encontrado no tenant.'), '409' => new Response('Estado ou idempotência conflitante.'), '413' => new Response('Arquivo excede o limite.'), '422' => new Response('Arquivo, mapping ou linha inválida.')];
    }

    /** @return array<string, mixed> */
    private function envelopeSchema(): array
    {
        $batch = ['type' => 'object', 'required' => ['id', 'type', 'status', 'file_name', 'file_hash', 'total_rows', 'valid_rows', 'success_rows', 'error_rows', 'skipped_rows'], 'properties' => [
            'id' => ['type' => 'integer', 'format' => 'int32'], 'type' => ['type' => 'string', 'enum' => ['BUSINESS_PARTNERS', 'MATERIALS', 'QUARRIES', 'STORAGE_LOCATIONS', 'BLOCKS', 'SLABS', 'LOTS', 'INVENTORY_OPENING', 'PRODUCTION_COSTS']], 'status' => ['type' => 'string'],
            'file_name' => ['type' => 'string'], 'original_file_name' => ['type' => 'string'], 'file_hash' => ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$'], 'file_size' => ['type' => 'integer'],
            'headers' => ['type' => 'array', 'items' => ['type' => 'string']], 'mapping' => ['type' => ['object', 'null'], 'additionalProperties' => ['type' => 'string']], 'encoding' => ['type' => 'string'], 'delimiter' => ['type' => 'string'],
            'total_rows' => ['type' => 'integer'], 'valid_rows' => ['type' => 'integer'], 'success_rows' => ['type' => 'integer'], 'error_rows' => ['type' => 'integer'], 'skipped_rows' => ['type' => 'integer'], 'failure_code' => ['type' => ['string', 'null']],
            'started_at' => ['type' => ['string', 'null'], 'format' => 'date-time'], 'completed_at' => ['type' => ['string', 'null'], 'format' => 'date-time'], 'created_at' => ['type' => 'string', 'format' => 'date-time'], 'updated_at' => ['type' => 'string', 'format' => 'date-time'],
        ]];
        $row = ['type' => 'object', 'required' => ['id', 'row_number', 'status', 'errors'], 'properties' => [
            'id' => ['type' => 'integer', 'format' => 'int32'], 'row_number' => ['type' => 'integer'], 'raw_data' => ['type' => 'object', 'additionalProperties' => true], 'normalized_data' => ['type' => ['object', 'null'], 'additionalProperties' => true],
            'status' => ['type' => 'string'], 'action' => ['type' => ['string', 'null'], 'enum' => ['CREATE', 'UPDATE', 'SKIP', 'ERROR', null]], 'errors' => ['type' => 'array', 'items' => ['type' => 'object']], 'entity_type' => ['type' => ['string', 'null']], 'entity_id' => ['type' => ['integer', 'null'], 'format' => 'int32'],
        ]];

        return ['type' => 'object', 'required' => ['data', 'meta', 'errors'], 'properties' => ['data' => ['oneOf' => [$batch, $row, ['type' => 'array', 'items' => ['oneOf' => [$batch, $row]]]]], 'meta' => ['type' => 'object'], 'errors' => ['type' => 'array']]];
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
