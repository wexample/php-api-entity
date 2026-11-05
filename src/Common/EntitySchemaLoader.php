<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Common;

use Symfony\Component\Yaml\Yaml;
use Wexample\Helpers\Helper\TextHelper;

/**
 * The entity schemas of `*.yml` files, read from directories in order: an
 * entity found again is merged over what was read — its keys replaced, its
 * properties replaced by name or added —, so that a client extending
 * another (an internal one over the public one) adds to its schemas.
 */
class EntitySchemaLoader
{
    /**
     * @param string[] $directories
     * @return array<string, array>
     */
    public function load(array $directories): array
    {
        $schemas = [];

        foreach ($directories as $directory) {
            $directory = rtrim($directory, '/');
            if (! is_dir($directory)) {
                continue;
            }

            foreach (glob($directory . '/*.yml') ?: [] as $file) {
                if (! is_file($file)) {
                    continue;
                }

                $schema = Yaml::parseFile($file);
                if (! is_array($schema)) {
                    continue;
                }

                $name = $schema['name'] ?? null;
                if (! is_string($name) || $name === '') {
                    continue;
                }

                $schema = $this->normalizeSchemaNaming($schema);
                $name = $schema['name'];

                $schemas[$name] = $this->mergeSchema($schemas[$name] ?? [], $schema);
            }
        }

        return $schemas;
    }

    /**
     * Schema files are generated from the registry, which names entities the
     * ORM way (snake_case tables: user_config); entity names follow the API
     * wire contract (camelCase: userConfig). Translating at read time keeps
     * the gap inside this loader — everything downstream, from schema lookup
     * to relationship targets, speaks entity names only.
     *
     * The nested `orm` block is left untouched: it describes the database,
     * not the API.
     */
    private function normalizeSchemaNaming(array $schema): array
    {
        $schema['name'] = TextHelper::toCamel($schema['name']);

        foreach ($schema['properties'] ?? [] as $index => $property) {
            if (! is_array($property)) {
                continue;
            }

            $target = $property['target'] ?? null;
            if (is_string($target) && $target !== '') {
                $schema['properties'][$index]['target'] = TextHelper::toCamel($target);
            }
        }

        return $schema;
    }

    private function mergeSchema(array $base, array $override): array
    {
        $baseProps = $this->normalizeProperties($base['properties'] ?? []);
        $overrideProps = $this->normalizeProperties($override['properties'] ?? []);

        $merged = array_replace($base, $override);
        $merged['properties'] = array_values(array_merge($baseProps, $overrideProps));

        return $merged;
    }

    /**
     * @param array<int, array> $properties
     * @return array<string, array>
     */
    private function normalizeProperties(array $properties): array
    {
        $normalized = [];

        foreach ($properties as $property) {
            if (! is_array($property)) {
                continue;
            }

            $name = $property['name'] ?? null;
            if (! is_string($name) || $name === '') {
                continue;
            }

            $normalized[$name] = $property;
        }

        return $normalized;
    }
}
