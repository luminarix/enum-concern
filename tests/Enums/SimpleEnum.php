<?php

declare(strict_types=1);

namespace Luminarix\EnumConcern\Tests\Enums;

use Luminarix\EnumConcern\EnumConcern;

enum SimpleEnum
{
    use EnumConcern;

    case A;
    case B;
    case C;
}
