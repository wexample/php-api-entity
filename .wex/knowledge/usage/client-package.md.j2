## A client package

A client of an api served by symfony-api is a package of entities and repositories over `AbstractApiEntitiesClient`, its schemas in `data/entity/*.yml`.

```php
class AcmeClient extends AbstractApiEntitiesClient
{
    protected function getRepositoryClasses(): array
    {
        return $this->discoverRepositoryClasses(dirname(__DIR__), 'AcmeClient\\Repository');
    }

    protected function getEntitySchemaDirectories(): array
    {
        return [dirname(__DIR__, 2) . '/data/entity'];
    }
}
```

- `getEntitySchemas()` reads the directories once (`EntitySchemaLoader`): an entity found again in a later one is merged over the earlier — its keys replaced, its properties replaced by name or added. A client extending another (an internal one over the public one) returns its parent's directories, then its own.
- Schema names written the ORM way (`user_config`) are read as entity names (`userConfig`), relation targets included; the `orm` block is left as it is.
- `discoverRepositoryClasses()` lists the `*Repository` classes of `<src>/Repository`.

## Generating the classes

```bash
php vendor/wexample/php-api-entity/bin/generate-api-entities path/to/acme-client [--abstract-entity=Parent\\Entity\\AbstractApiEntity]
```

One entity and one repository class per schema, in the package's PSR-4 namespace (`Entity\`, `Repository\`). A class already there is left alone: written once, then the package's to extend. The entities extend the package's own `Entity\AbstractApiEntity` when it has one, php-api-entity's otherwise, or the class given. The script needs no autoloader: it runs from wherever the package lies.
