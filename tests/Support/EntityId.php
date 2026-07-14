<?php

declare(strict_types=1);

namespace App\Tests\Support;

final class EntityId
{
    public static function assign(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}
