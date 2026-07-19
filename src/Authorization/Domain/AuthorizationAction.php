<?php

declare(strict_types=1);

namespace App\Authorization\Domain;

enum AuthorizationAction: string
{
    case ImportRead = 'IMPORT_READ';
    case ImportWrite = 'IMPORT_WRITE';
    case AuditRead = 'AUDIT_READ';
    case ManageMembers = 'MANAGE_MEMBERS';
    case AssignOwner = 'ASSIGN_OWNER';
}
