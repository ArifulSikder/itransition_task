<?php

namespace App\Entity;

enum UserStatus: string
{
    case Unverified = 'unverified';
    case Active = 'active';
    case Blocked = 'blocked';
}
