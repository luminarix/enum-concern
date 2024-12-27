<?php

declare(strict_types=1);

namespace Luminarix\EnumConcern\Tests;

use Luminarix\EnumConcern\EnumConcern;

enum UseTraitUnitEnum
{
    use EnumConcern;

    case A;
}
