<?php

declare(strict_types=1);

namespace App\Tests\Receivables\Support;

use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Application\Service\ReceivableStatusResolver;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Repository\ReceivableCriteria;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Receivables\Domain\Service\AgingClassifier;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Support\EntityId;

final class InMemoryReceivableRepository implements ReceivableRepository
{
    /** @var array<int, Receivable> */
    private array $items = [];

    public function save(Organization $organization, Receivable $receivable): void
    {
        if ($receivable->organization() !== $organization || $receivable->customer()->organization() !== $organization) {
            throw new DomainException('RECEIVABLE_TENANT_MISMATCH', 'Tenant mismatch.', 403);
        }
        if (null !== $receivable->externalId() && $this->existsByExternalKey($organization, $receivable->source(), $receivable->externalId(), $receivable->id())) {
            throw new DomainException('RECEIVABLE_DUPLICATE_EXTERNAL_KEY', 'Duplicate external key.', 409, 'external_id');
        }
        if (null === $receivable->id()) {
            EntityId::assign($receivable, count($this->items) + 1);
        }
        $this->items[$receivable->requireId()] = $receivable;
    }

    public function findByIdAndOrganization(int $id, Organization $organization): ?Receivable
    {
        $receivable = $this->items[$id] ?? null;

        return null !== $receivable && $receivable->organization() === $organization ? $receivable : null;
    }

    public function findByIdAndOrganizationForUpdate(int $id, Organization $organization): ?Receivable
    {
        return $this->findByIdAndOrganization($id, $organization);
    }

    public function existsByExternalKey(Organization $organization, string $source, string $externalId, ?int $exceptId = null): bool
    {
        foreach ($this->items as $receivable) {
            if ($receivable->organization() === $organization && $receivable->source() === $source && $receivable->externalId() === $externalId && $receivable->id() !== $exceptId) {
                return true;
            }
        }

        return false;
    }

    public function list(Organization $organization, ReceivableCriteria $criteria): array
    {
        $items = array_values(array_filter($this->items, fn (Receivable $receivable): bool => $this->matches($receivable, $organization, $criteria)));
        usort($items, static function (Receivable $left, Receivable $right) use ($criteria): int {
            $comparison = match (ltrim($criteria->sort, '-')) {
                'created_at' => $left->createdAt() <=> $right->createdAt(),
                'open_amount' => (new ReceivableAmount($left->openAmount()))->compare(new ReceivableAmount($right->openAmount())),
                default => $left->dueDate() <=> $right->dueDate(),
            };
            $comparison = 0 !== $comparison ? $comparison : $left->requireId() <=> $right->requireId();

            return str_starts_with($criteria->sort, '-') ? -$comparison : $comparison;
        });

        return array_slice($items, ($criteria->page - 1) * $criteria->perPage, $criteria->perPage);
    }

    public function countMatching(Organization $organization, ReceivableCriteria $criteria): int
    {
        return count(array_filter($this->items, fn (Receivable $receivable): bool => $this->matches($receivable, $organization, $criteria)));
    }

    /** @return list<Receivable> */
    public function all(): array
    {
        return array_values($this->items);
    }

    private function matches(Receivable $receivable, Organization $organization, ReceivableCriteria $criteria): bool
    {
        if ($receivable->organization() !== $organization || (null !== $criteria->customerId && $receivable->customer()->id() !== $criteria->customerId)) {
            return false;
        }
        if (null !== $criteria->dueDateFrom && $receivable->dueDate() < $criteria->dueDateFrom || null !== $criteria->dueDateTo && $receivable->dueDate() > $criteria->dueDateTo) {
            return false;
        }
        $openAmount = new ReceivableAmount($receivable->openAmount());
        if (null !== $criteria->amountMin && $openAmount->compare(new ReceivableAmount($criteria->amountMin)) < 0 || null !== $criteria->amountMax && $openAmount->compare(new ReceivableAmount($criteria->amountMax)) > 0) {
            return false;
        }
        if (null !== $criteria->search) {
            $haystack = mb_strtolower(implode(' ', [$receivable->documentNumber(), $receivable->externalId() ?? '', $receivable->customer()->legalName(), $receivable->customer()->tradeName() ?? '']));
            if (!str_contains($haystack, mb_strtolower($criteria->search))) {
                return false;
            }
        }
        $resolver = new ReceivableStatusResolver();
        $status = $resolver->resolve($receivable, $criteria->referenceDate);
        if (null !== $criteria->status && $status !== $criteria->status || null !== $criteria->overdue && (\App\Receivables\Domain\Enum\ReceivableStatus::Overdue === $status) !== $criteria->overdue) {
            return false;
        }
        if (null !== $criteria->agingBucket && (new AgingClassifier($resolver))->classify($receivable, $criteria->referenceDate) !== $criteria->agingBucket) {
            return false;
        }

        return true;
    }
}
