<?php

declare(strict_types=1);

namespace Luminarix\EnumConcern\Tests\Enums;

use Luminarix\EnumConcern\EnumConcern;

enum StringBackedEnum: string
{
    use EnumConcern;

    case A = 'a';
    case B = 'b';
    case C = 'c';
}
