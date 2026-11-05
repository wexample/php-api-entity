<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Generator;

use RuntimeException;

/**
 * The entity and repository classes of a client package, one of each per
 * schema of its `data/entity/*.yml`. A class already there is left alone:
 * written once, then the package's to extend.
 *
 * Plain PHP, no dependency: the generator runs from wherever the package
 * lies, its own autoloader or none.
 */
final class ApiEntityClassGenerator
{
    public const string ABSTRACT_ENTITY_DEFAULT = 'Wexample\\PhpApiEntity\\Common\\AbstractApiEntity';

    private readonly string $rootNamespace;

    /**
     * @param string $packageDir The client package: its `composer.json`, `data/entity/`, `src/`.
     * @param string|null $abstractEntityClass What the entities extend: the package's own
     *     `Entity\AbstractApiEntity` when it has one, php-api-entity's otherwise.
     */
    public function __construct(
        private readonly string $packageDir,
        private ?string $abstractEntityClass = null,
    ) {
        $this->rootNamespace = self::readRootNamespace($packageDir);
        $this->abstractEntityClass ??= is_file($packageDir . '/src/Entity/AbstractApiEntity.php')
            ? $this->rootNamespace . '\\Entity\\AbstractApiEntity'
            : self::ABSTRACT_ENTITY_DEFAULT;
    }

    /**
     * @return array{created: list<string>, skipped: int} The files written, and how many were already there.
     */
    public function generate(): array
    {
        $files = glob($this->packageDir . '/data/entity/*.yml');

        if (false === $files || ! is_dir($this->packageDir . '/data/entity')) {
            throw new RuntimeException('No data/entity directory in ' . $this->packageDir . '.');
        }

        sort($files);
        $report = ['created' => [], 'skipped' => 0];

        foreach ($files as $file) {
            $className = self::toStudlyCase(pathinfo($file, PATHINFO_FILENAME));

            if ('' === $className || 'AbstractApiEntity' === $className) {
                continue;
            }

            foreach ($this->buildClasses($className) as $path => $content) {
                if (is_file($path)) {
                    $report['skipped']++;

                    continue;
                }

                if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0775, true) && ! is_dir(dirname($path))) {
                    throw new RuntimeException('Cannot create ' . dirname($path) . '.');
                }

                file_put_contents($path, $content);
                $report['created'][] = $path;
            }
        }

        return $report;
    }

    /**
     * @return array<string, string> Each class's path, and its content.
     */
    private function buildClasses(string $className): array
    {
        $placeholders = [
            '{{CLASS_NAME}}' => $className,
            '{{ENTITY_NAMESPACE}}' => $this->rootNamespace . '\\Entity',
            '{{REPOSITORY_NAMESPACE}}' => $this->rootNamespace . '\\Repository',
            '{{ABSTRACT_ENTITY_CLASS}}' => '\\' . ltrim($this->abstractEntityClass, '\\'),
        ];

        return [
            $this->packageDir . '/src/Entity/' . $className . '.php' => strtr(self::readTemplate('Entity'), $placeholders),
            $this->packageDir . '/src/Repository/' . $className . 'Repository.php' => strtr(self::readTemplate('Repository'), $placeholders),
        ];
    }

    private static function readTemplate(string $name): string
    {
        $content = file_get_contents(dirname(__DIR__, 2) . '/resources/template/' . $name . '.php.tpl');

        if (false === $content || '' === $content) {
            throw new RuntimeException('Missing template ' . $name . '.');
        }

        return $content;
    }

    /**
     * `user_config` → `UserConfig`.
     */
    public static function toStudlyCase(string $value): string
    {
        $value = (string) preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim($value)));

        return str_replace(' ', '', ucwords(trim($value)));
    }

    private static function readRootNamespace(string $packageDir): string
    {
        $composer = json_decode((string) @file_get_contents($packageDir . '/composer.json'), true);
        $autoload = $composer['autoload']['psr-4'] ?? [];

        if (! is_array($autoload) || [] === $autoload) {
            throw new RuntimeException('No PSR-4 namespace in ' . $packageDir . '/composer.json.');
        }

        return rtrim((string) array_key_first($autoload), '\\');
    }
}
