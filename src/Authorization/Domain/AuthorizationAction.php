<?php

declare(strict_types=1);

namespace App\Authorization\Domain;

enum AuthorizationAction: string
{
    case BusinessPartnerRead = 'BUSINESS_PARTNER_READ';
    case BusinessPartnerWrite = 'BUSINESS_PARTNER_WRITE';
    case MaterialRead = 'MATERIAL_READ';
    case MaterialWrite = 'MATERIAL_WRITE';
    case QuarryRead = 'QUARRY_READ';
    case QuarryWrite = 'QUARRY_WRITE';
    case StorageLocationRead = 'STORAGE_LOCATION_READ';
    case StorageLocationWrite = 'STORAGE_LOCATION_WRITE';
    case MachineRead = 'MACHINE_READ';
    case MachineWrite = 'MACHINE_WRITE';
    case BlockRead = 'BLOCK_READ';
    case BlockWrite = 'BLOCK_WRITE';
    case BlockReceive = 'BLOCK_RECEIVE';
    case BlockMove = 'BLOCK_MOVE';
    case ProductionOrderRead = 'PRODUCTION_ORDER_READ';
    case ProductionOrderWrite = 'PRODUCTION_ORDER_WRITE';
    case ProductionOrderStart = 'PRODUCTION_ORDER_START';
    case ProductionOrderComplete = 'PRODUCTION_ORDER_COMPLETE';
    case ProductionOrderCancel = 'PRODUCTION_ORDER_CANCEL';
    case SlabRead = 'SLAB_READ';
    case SlabWrite = 'SLAB_WRITE';
    case SlabClassify = 'SLAB_CLASSIFY';
    case SlabMove = 'SLAB_MOVE';
    case LotRead = 'LOT_READ';
    case LotWrite = 'LOT_WRITE';
    case InventoryRead = 'INVENTORY_READ';
    case InventoryMove = 'INVENTORY_MOVE';
    case InventoryAdjust = 'INVENTORY_ADJUST';
    case TraceabilityRead = 'TRACEABILITY_READ';
    case YieldRead = 'YIELD_READ';
    case ProductionCostRead = 'PRODUCTION_COST_READ';
    case ProductionCostWrite = 'PRODUCTION_COST_WRITE';
    case PricingRead = 'PRICING_READ';
    case PricingWrite = 'PRICING_WRITE';
    case ImportRead = 'IMPORT_READ';
    case ImportWrite = 'IMPORT_WRITE';
    case AuditRead = 'AUDIT_READ';
    case ManageMembers = 'MANAGE_MEMBERS';
    case AssignOwner = 'ASSIGN_OWNER';
}
