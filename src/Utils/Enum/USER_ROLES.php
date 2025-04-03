<?php

namespace App\Utils\Enum;

enum USER_ROLES: String  {
    case USER= 'ROLE_USER';
    case TECH= 'ROLE_TECH';
    case ADMIN= 'ROLE_ADMIN';
    case SUPER_ADMIN= 'ROLE_SUPER_ADMIN';
}
