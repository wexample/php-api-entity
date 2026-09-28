<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Fixtures;

use Wexample\PhpApiEntity\Common\AbstractApiEntity;

class DemoUser extends AbstractApiEntity
{
    public static function getEntityName(): string
    {
        return 'demoUser';
    }
}
