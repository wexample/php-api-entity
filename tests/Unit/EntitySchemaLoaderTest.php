<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\PhpApiEntity\Common\EntitySchemaLoader;

class EntitySchemaLoaderTest extends TestCase
{
    public function testALaterDirectoryAddsToTheSchemasOfAnEarlierOne(): void
    {
        $public = $this->createDirectory([
            'user_config.yml' => "name: user_config\nvisibility: public\nproperties:\n  - { name: label, type: string }\n  - { name: owner, type: relation, target: user_account }\n",
        ]);
        $internal = $this->createDirectory([
            'user_config.yml' => "name: user_config\nvisibility: internal\nproperties:\n  - { name: label, type: text }\n  - { name: secret, type: string }\n",
        ]);

        $schemas = (new EntitySchemaLoader())->load([$public, $internal, '/no/such/directory']);

        $this->assertSame(['userConfig'], array_keys($schemas));
        $this->assertSame('internal', $schemas['userConfig']['visibility']);
        $this->assertSame(
            [['label', 'text'], ['owner', 'relation'], ['secret', 'string']],
            array_map(fn (array $property) => [$property['name'], $property['type']], $schemas['userConfig']['properties'])
        );
        // Relation targets are named as entities are.
        $this->assertSame('userAccount', $schemas['userConfig']['properties'][1]['target']);
    }

    /**
     * @param array<string, string> $files
     */
    private function createDirectory(array $files): string
    {
        $directory = sys_get_temp_dir() . '/php-api-entity-' . bin2hex(random_bytes(4));
        mkdir($directory);

        foreach ($files as $name => $content) {
            file_put_contents($directory . '/' . $name, $content);
        }

        return $directory;
    }
}
