<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'OWNER';
    case Editor = 'EDITOR';
    case Viewer = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Chủ sở hữu',
            self::Editor => 'Biên tập viên',
            self::Viewer => 'Thành viên xem',
        };
    }
}
