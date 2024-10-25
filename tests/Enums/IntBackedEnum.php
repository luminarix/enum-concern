<?php

declare(strict_types=1);

namespace Luminarix\EnumConcern\Tests\Enums;

use Luminarix\EnumConcern\EnumConcern;

enum IntBackedEnum: int
{
    use EnumConcern;

    case A = 1;
    case B = 2;
    case C = 3;
}
