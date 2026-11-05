<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\PhpApiEntity\Generator\ApiEntityClassGenerator;

class ApiEntityClassGeneratorTest extends TestCase
{
    private string $packageDir;

    protected function setUp(): void
    {
        $this->packageDir = sys_get_temp_dir() . '/php-api-entity-client-' . bin2hex(random_bytes(4));
        mkdir($this->packageDir . '/data/entity', 0775, true);
        file_put_contents($this->packageDir . '/composer.json', json_encode(['autoload' => ['psr-4' => ['AcmeClient\\' => 'src/']]]));
        touch($this->packageDir . '/data/entity/user_config.yml');
        touch($this->packageDir . '/data/entity/project.yml');
    }

    public function testAnEntityAndItsRepositoryPerSchemaWrittenOnce(): void
    {
        $report = (new ApiEntityClassGenerator($this->packageDir))->generate();

        $this->assertCount(4, $report['created']);
        $entity = file_get_contents($this->packageDir . '/src/Entity/UserConfig.php');
        $this->assertStringContainsString("namespace AcmeClient\\Entity;", $entity);
        $this->assertStringContainsString('class UserConfig extends \\' . ApiEntityClassGenerator::ABSTRACT_ENTITY_DEFAULT, $entity);
        $repository = file_get_contents($this->packageDir . '/src/Repository/ProjectRepository.php');
        $this->assertStringContainsString("use AcmeClient\\Entity\\Project;", $repository);
        $this->assertStringContainsString('return Project::class;', $repository);

        // Written once: the package's to extend afterwards.
        file_put_contents($this->packageDir . '/src/Entity/UserConfig.php', '<?php // extended');
        $again = (new ApiEntityClassGenerator($this->packageDir))->generate();
        $this->assertSame(['created' => [], 'skipped' => 4], $again);
        $this->assertSame('<?php // extended', file_get_contents($this->packageDir . '/src/Entity/UserConfig.php'));
    }

    public function testThePackagesOwnBaseEntityOrTheOneAsked(): void
    {
        mkdir($this->packageDir . '/src/Entity', 0775, true);
        touch($this->packageDir . '/src/Entity/AbstractApiEntity.php');
        (new ApiEntityClassGenerator($this->packageDir))->generate();
        $this->assertStringContainsString('extends \\AcmeClient\\Entity\\AbstractApiEntity', file_get_contents($this->packageDir . '/src/Entity/Project.php'));

        unlink($this->packageDir . '/src/Entity/Project.php');
        (new ApiEntityClassGenerator($this->packageDir, 'OtherClient\\Entity\\AbstractApiEntity'))->generate();
        $this->assertStringContainsString('extends \\OtherClient\\Entity\\AbstractApiEntity', file_get_contents($this->packageDir . '/src/Entity/Project.php'));
    }
}
