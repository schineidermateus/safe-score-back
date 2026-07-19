<?php

declare(strict_types=1);

namespace App\Imports\Domain\Enum;

enum ImportType: string
{
    case BusinessPartners = 'BUSINESS_PARTNERS';
    case Materials = 'MATERIALS';
    case Quarries = 'QUARRIES';
    case StorageLocations = 'STORAGE_LOCATIONS';
    case Blocks = 'BLOCKS';
    case Slabs = 'SLABS';
    case Lots = 'LOTS';
    case InventoryOpening = 'INVENTORY_OPENING';
    case ProductionCosts = 'PRODUCTION_COSTS';

    public function implemented(): bool
    {
        return false;
    }
}
