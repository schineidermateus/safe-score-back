<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Security;

enum OrganizationRole: string
{
    case Admin = 'ROLE_ORGANIZATION_ADMIN';
    case Manager = 'ROLE_ORGANIZATION_MANAGER';
    case Analyst = 'ROLE_ORGANIZATION_ANALYST';
    case Viewer = 'ROLE_ORGANIZATION_VIEWER';
}
