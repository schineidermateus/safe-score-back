<?php

namespace App\Shared\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestService
{
    private ?array $baseContent = null;
    private Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    private function ensureContentLoaded(): void
    {
        if ($this->baseContent === null) {
            $raw = $this->request->getContent();
            $this->baseContent = $raw ? json_decode($raw, true) : [];
        }
    }

    public function requirePost(string $key)
    {
        $this->ensureContentLoaded();

        return $this->baseContent[$key] ?? null;
    }

    public function getContent(): array
    {
        $this->ensureContentLoaded();
        return $this->baseContent;
    }
}
