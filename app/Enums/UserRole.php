<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'OWNER';
    case Editor = 'EDITOR';
    case Viewer = 'VIEWER';
}
