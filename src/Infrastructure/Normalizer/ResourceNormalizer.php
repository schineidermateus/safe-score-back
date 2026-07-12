<?php

namespace App\Infrastructure\Normalizer;

use App\Shared\Interface\ResourceInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ResourceNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private array $processed = [];

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ResourceInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ResourceInterface::class => true,
        ];
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array {
        if (!($data instanceof ResourceInterface)) {
            return [];
        }

        $hash = spl_object_id($data);
        if (isset($this->processed[$hash])) {
            return []; // evita loops em relações cíclicas
        }

        $this->processed[$hash] = true;

        $array = $data->__toArray();

        $normalized = [];

        foreach ($array as $key => $value) {
            if ($value instanceof ResourceInterface) {
                $normalized[$key] = $this->normalize($value, $format, $context);
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeArray($value, $format, $context);
                continue;
            }

            if ($value !== null) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @throws ExceptionInterface
     */
    private function normalizeArray(array $items, ?string $format, array $context): array
    {
        $normalized = [];

        foreach ($items as $key => $item) {
            if ($item instanceof ResourceInterface) {
                $normalized[$key] = $this->normalize($item, $format, $context);
                continue;
            }

            if (is_array($item)) {
                $normalized[$key] = $this->normalizeArray($item, $format, $context);
                continue;
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }
}
