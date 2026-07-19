<?php

declare(strict_types=1);

namespace App\Imports\Application\Schema;

use App\Imports\Domain\Enum\ImportType;
use App\Shared\Domain\Exception\DomainException;

final class ImportSchemaRegistry
{
    /** @var array<string, ImportSchema> */
    private array $schemas;

    public function __construct()
    {
        $this->schemas = [];
    }

    public function get(ImportType $type): ImportSchema
    {
        return $this->schemas[$type->value] ?? throw new DomainException('IMPORT_TYPE_NOT_IMPLEMENTED', sprintf('O tipo %s ainda nÃ£o possui importer implementado.', $type->value), 422, 'type');
    }

    /**
     * @param list<string>          $headers
     * @param array<string, string> $mapping
     */
    public function validateMapping(ImportType $type, array $headers, array $mapping): void
    {
        $schema = $this->get($type);
        if ([] === $mapping) {
            throw new DomainException('IMPORT_INVALID_MAPPING', 'O mapeamento não pode ser vazio.', 422, 'mapping');
        }
        $targets = [];
        foreach ($mapping as $header => $field) {
            if (!in_array($header, $headers, true)) {
                throw new DomainException('IMPORT_INVALID_MAPPING', sprintf('A coluna "%s" não existe no arquivo.', $header), 422, 'mapping');
            }
            if (!in_array($field, $schema->fields, true)) {
                throw new DomainException('IMPORT_INVALID_MAPPING', sprintf('O campo "%s" não pertence ao tipo %s.', $field, $type->value), 422, 'mapping');
            }
            if (in_array($field, $targets, true)) {
                throw new DomainException('IMPORT_INVALID_MAPPING', sprintf('O campo "%s" foi mapeado mais de uma vez.', $field), 422, 'mapping');
            }
            $targets[] = $field;
        }
        foreach ($schema->required as $required) {
            if (!in_array($required, $targets, true)) {
                throw new DomainException('IMPORT_MISSING_REQUIRED_COLUMN', sprintf('O campo obrigatório "%s" não foi mapeado.', $required), 422, 'mapping');
            }
        }
        foreach ($schema->oneOf as $alternatives) {
            if ([] === array_intersect($alternatives, $targets)) {
                throw new DomainException('IMPORT_MISSING_REQUIRED_COLUMN', sprintf('Mapeie ao menos um destes campos: %s.', implode(', ', $alternatives)), 422, 'mapping');
            }
        }
    }
}
