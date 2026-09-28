<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Fixtures;

use Wexample\PhpApiEntity\Common\AbstractApiRepository;

class DemoUserRepository extends AbstractApiRepository
{
    public static function getEntityType(): string
    {
        return DemoUser::class;
    }
}
