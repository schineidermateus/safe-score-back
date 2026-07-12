<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Http;

use App\Identity\Domain\Security\AuthenticatedUser;
use App\Organizations\Application\Context\OrganizationContext;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 8)]
final readonly class OrganizationContextSubscriber
{
    public function __construct(
        private Security $security,
        private OrganizationContext $context,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->context->clear();
        $user = $this->security->getUser();

        if ($user instanceof AuthenticatedUser) {
            $this->context->set($user->activeOrganizationId());
        }
    }
}
