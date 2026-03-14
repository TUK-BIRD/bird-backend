<?php

namespace App\Enums;

enum UserSpaceRole: string
{
    case OWNER = 'OWNER';
    case MEMBER = 'MEMBER';
}
