<?php

declare(strict_types=1);

namespace App\Authorization\Domain;

enum AuthorizationAction: string
{
    case ViewData = 'VIEW_DATA';
    case ManageCustomers = 'MANAGE_CUSTOMERS';
    case CreditLimitRead = 'CREDIT_LIMIT_READ';
    case CreditLimitWrite = 'CREDIT_LIMIT_WRITE';
    case CreditLimitRevoke = 'CREDIT_LIMIT_REVOKE';
    case ManageReceivables = 'MANAGE_RECEIVABLES';
    case ImportData = 'IMPORT_DATA';
    case ResolveAlerts = 'RESOLVE_ALERTS';
    case RecalculateScore = 'RECALCULATE_SCORE';
    case ManageMembers = 'MANAGE_MEMBERS';
    case AssignOwner = 'ASSIGN_OWNER';
}
