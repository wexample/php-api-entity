<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Fixtures;

use Wexample\PhpApiEntity\Common\AbstractApiEntity;

class DemoProject extends AbstractApiEntity
{
    public static function getEntityName(): string
    {
        return 'demoProject';
    }
}
