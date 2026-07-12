<?php

namespace App\Shared\Interface;

interface RequestDTOInterface
{
    public static function fromArray(array $data): self;
}
