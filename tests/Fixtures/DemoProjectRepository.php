<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Fixtures;

use Wexample\PhpApiEntity\Common\AbstractApiRepository;

class DemoProjectRepository extends AbstractApiRepository
{
    public static function getEntityType(): string
    {
        return DemoProject::class;
    }
}
