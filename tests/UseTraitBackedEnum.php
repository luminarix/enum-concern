<?php

declare(strict_types=1);

namespace Luminarix\EnumConcern\Tests;

use Luminarix\EnumConcern\EnumConcern;

enum UseTraitBackedEnum: int
{
    use EnumConcern;

    case A = 1;
}
