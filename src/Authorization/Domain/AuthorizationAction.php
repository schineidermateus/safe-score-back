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
    case ReceivableRead = 'RECEIVABLE_READ';
    case ReceivableWrite = 'RECEIVABLE_WRITE';
    case ReceivablePaymentRegister = 'RECEIVABLE_PAYMENT_REGISTER';
    case ReceivableCancel = 'RECEIVABLE_CANCEL';
    case ImportRead = 'IMPORT_READ';
    case ImportCreate = 'IMPORT_CREATE';
    case ImportValidate = 'IMPORT_VALIDATE';
    case ImportProcess = 'IMPORT_PROCESS';
    case ImportCancel = 'IMPORT_CANCEL';
    case FinancialIndicatorsRead = 'FINANCIAL_INDICATORS_READ';
    case ResolveAlerts = 'RESOLVE_ALERTS';
    case RecalculateScore = 'RECALCULATE_SCORE';
    case ManageMembers = 'MANAGE_MEMBERS';
    case AssignOwner = 'ASSIGN_OWNER';
}
