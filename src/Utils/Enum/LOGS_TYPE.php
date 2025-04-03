<?php

namespace App\Utils\Enum;

enum LOGS_TYPE: string
{
    case ERROR = 'ERROR';
    case ADD = 'ADD';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case MAIL = 'MAIL';
}