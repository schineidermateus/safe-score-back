<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final readonly class FinancialIndicatorsOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    /** @param array<string, mixed> $context */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $openApi->getPaths()->addPath('/api/v1/customers/{id}/financial-summary', new PathItem(
            get: new Operation(
                operationId: 'customer_financial_indicators_get',
                tags: ['Financial Indicators'],
                summary: 'Calcula indicadores financeiros brutos do cliente',
                description: 'A referência classifica os saldos atualmente persistidos; não reconstrói uma posição financeira histórica.',
                parameters: [
                    new Parameter('id', 'path', 'ID inteiro do cliente.', true, schema: ['type' => 'integer', 'minimum' => 1]),
                    new Parameter('reference_date', 'query', 'Data civil única usada em todos os cálculos.', true, schema: ['type' => 'string', 'format' => 'date']),
                ],
                responses: [
                    '200' => new Response('Indicadores calculados.', content: new \ArrayObject(['application/json' => ['schema' => $this->responseSchema()]])),
                    '403' => new Response('Acesso negado.'),
                    '404' => new Response('Cliente não encontrado no tenant atual.'),
                    '422' => new Response('Data de referência inválida.'),
                ],
            ),
        ));

        return $openApi;
    }

    /** @return array<string, mixed> */
    private function responseSchema(): array
    {
        $money = ['type' => 'string', 'pattern' => '^-?\\d+\\.\\d{2}$'];
        $result = static fn (array $valueSchema): array => ['type' => 'object', 'required' => ['status', 'value'], 'properties' => [
            'status' => ['type' => 'string', 'enum' => ['AVAILABLE', 'NO_ACTIVE_LIMIT', 'NO_EXPOSURE', 'INSUFFICIENT_HISTORY', 'INCONSISTENT_DATA', 'NO_PORTFOLIO']],
            'value' => ['oneOf' => [$valueSchema, ['type' => 'null']]],
        ]];
        $percentageResult = $result(['type' => 'string', 'pattern' => '^\\d+\\.\\d{2}$']);

        $dataRequired = [
            'customer_id', 'reference_date', 'currency', 'credit_limit', 'exposure', 'available_credit', 'utilization_percentage',
            'overdue_amount', 'overdue_percentage', 'maximum_overdue_days', 'paid_receivables_count', 'on_time_paid_receivables_count',
            'late_paid_receivables_count', 'on_time_payment_percentage', 'average_payment_delay_days', 'maximum_payment_delay_days',
            'portfolio_concentration_percentage', 'data_quality_score', 'data_quality_level', 'data_quality_reasons', 'last_data_update',
        ];

        return ['type' => 'object', 'required' => ['data', 'meta', 'errors'], 'additionalProperties' => false, 'properties' => [
            'data' => ['type' => 'object', 'required' => $dataRequired, 'additionalProperties' => false, 'properties' => [
                'customer_id' => ['type' => 'integer'], 'reference_date' => ['type' => 'string', 'format' => 'date'], 'currency' => ['type' => 'string'],
                'credit_limit' => $result($money), 'exposure' => $money, 'available_credit' => $result($money),
                'utilization_percentage' => $percentageResult, 'overdue_amount' => $money, 'overdue_percentage' => $percentageResult,
                'maximum_overdue_days' => ['type' => 'integer', 'minimum' => 0], 'paid_receivables_count' => ['type' => 'integer', 'minimum' => 0],
                'on_time_paid_receivables_count' => ['type' => 'integer', 'minimum' => 0], 'late_paid_receivables_count' => ['type' => 'integer', 'minimum' => 0],
                'on_time_payment_percentage' => $percentageResult,
                'average_payment_delay_days' => $result(['type' => 'string', 'pattern' => '^\\d+\\.\\d{2}$']),
                'maximum_payment_delay_days' => $result(['type' => 'integer', 'minimum' => 0]),
                'portfolio_concentration_percentage' => $percentageResult,
                'data_quality_score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                'data_quality_level' => ['type' => 'string'], 'data_quality_reasons' => ['type' => 'array', 'items' => ['type' => 'string']],
                'last_data_update' => ['type' => ['string', 'null'], 'format' => 'date-time'],
            ]],
            'meta' => ['type' => 'object'], 'errors' => ['type' => 'array'],
        ]];
    }
}
