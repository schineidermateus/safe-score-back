<?php

declare(strict_types=1);

namespace App\Receivables\Application\Service;

use App\Receivables\Application\DTO\ReceivableOutput;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Entity\ReceivablePayment;
use App\Receivables\Domain\Service\AgingClassifier;
use App\Receivables\Domain\Service\ReceivableStatusResolverInterface;

final readonly class ReceivableOutputFactory
{
    public function __construct(private ReceivableStatusResolverInterface $resolver, private AgingClassifier $aging)
    {
    }

    /** @param list<ReceivablePayment> $payments */
    public function create(Receivable $receivable, \DateTimeImmutable $referenceDate, array $payments = []): ReceivableOutput
    {
        return ReceivableOutput::fromEntity($receivable, $referenceDate, $this->resolver, $this->aging, $payments);
    }
}
