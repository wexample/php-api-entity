<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Fixtures;

use Wexample\PhpApiEntity\Common\AbstractApiEntitiesClient;

class DemoClient extends AbstractApiEntitiesClient
{
    protected function getRepositoryClasses(): array
    {
        return [
            DemoProjectRepository::class,
            DemoUserRepository::class,
        ];
    }

    public function getEntitySchemas(): array
    {
        return [
            'demoProject' => [
                'name' => 'demoProject',
                'properties' => [
                    ['name' => 'title', 'type' => 'string'],
                    ['name' => 'archived', 'type' => 'bool'],
                    ['name' => 'owner', 'type' => 'relation', 'target' => 'demoUser', 'nullable' => true],
                ],
            ],
            'demoUser' => [
                'name' => 'demoUser',
                'properties' => [
                    ['name' => 'name', 'type' => 'string'],
                ],
            ],
        ];
    }
}
