<?php

declare(strict_types=1);

namespace App\Authorization\Domain;

enum AuthorizationAction: string
{
    case ViewData = 'VIEW_DATA';
    case ManageCustomers = 'MANAGE_CUSTOMERS';
    case ManageCredit = 'MANAGE_CREDIT';
    case ManageReceivables = 'MANAGE_RECEIVABLES';
    case ImportData = 'IMPORT_DATA';
    case ResolveAlerts = 'RESOLVE_ALERTS';
    case RecalculateScore = 'RECALCULATE_SCORE';
    case ManageMembers = 'MANAGE_MEMBERS';
    case AssignOwner = 'ASSIGN_OWNER';
}
