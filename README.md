# php-api-entity

Version: 2.0.2

`wexample/php-api-entity` is the PHP client side of the convention `wexample/symfony-api` serves: responses wrapped in a `{type, code, message?, data}` envelope, items shaped `{type, entity, metadata?, relationships?}`, and entity schemas describing each field. A client extends src/Common/AbstractApiEntitiesClient.php, declares its repositories and schemas, and gets back `AbstractApiEntity` objects validated field by field, with their relationships resolved.

Transport — base URL, bearer token, timeouts, retries, rate limit, `ApiException` — comes from `wexample/php-api`, which this package extends. A client for a service that does not follow the wexample convention uses `wexample/php-api` alone. The TypeScript counterpart is `@wexample/js-api-entity`.

```php
class ShopClient extends AbstractApiEntitiesClient
{
    protected function getRepositoryClasses(): array
    {
        return [ProductRepository::class];
    }

    public function getEntitySchemas(): array
    {
        return $this->schemas; // exported by symfony-api, keyed by entity name
    }
}

$products = $client->getRepository(Product::class)->fetchList(page: 0, length: 20);
```

## Table of Contents

- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

Everything lives under `Wexample\PhpApiEntity\` (PSR-4 on `src/`, declared in composer.json): `Common/` holds the classes an application extends, `Helper/` the stateless envelope and schema parsing, `Exceptions/` the two errors this layer adds. It depends on `wexample/php-api` for transport and on `wexample/php-helpers` for name conversions.

### The client

src/Common/AbstractApiEntitiesClient.php extends `Wexample\PhpApi\Common\AbstractApiClient`, so every request goes through php-api's `Client::request()` — options, retries and `ApiException` included. Its constructor instantiates an `ApiEntityManager` from `getRepositoryClasses()` and a fresh `ApiEntityRegistry`. A concrete client implements two abstract methods: `getRepositoryClasses()` and `getEntitySchemas()`, the schemas exported by the remote symfony-api keyed by entity name. `getRepository($entity)` accepts either an entity name or an `AbstractApiEntity` class-string.

src/Common/ApiEntityManager.php owns the entity-name → repository table. At construction it calls `$repositoryClass::getEntityType()` on each repository, refuses anything not extending `AbstractApiEntity`, and indexes by `$entityType::getEntityName()`. Repositories are built lazily; an unknown name throws `InvalidArgumentException` listing the registered ones.

### The path of a call

`$client->getRepository(Article::class)->fetch('abc')` goes:

1. `ApiEntityManager::get()` resolves the name and lazily constructs the repository with the client.
2. src/Common/AbstractApiRepository.php`::fetch()` builds `article/show/abc` — `buildPath()` kebab-cases the entity name, `rawurlencode` protects the identifier — and calls `$this->client->requestJson(HttpMethod::GET, …)`.
3. php-api sends it; a status `>= 400` becomes `ApiException`, the body is decoded and required to be an array.
4. `extractPayload()` hands the response to `ApiEnvelopeHelper::unwrap()`, which throws `ApiEnvelopeException` on `type === 'error'` (message = the server error key, e.g. `ERR_INVALID_CREDENTIALS`) or on a missing `data`, and returns `$response['data']` otherwise. `fetchList()` adds `extractItems()`, requiring an `items` array.
5. `hydrateFromApiItem()` splits the item into `[entity, metadata, relationships]`, rejecting anything without a string `type` and an `entity` object, then checks `type` equals this repository's entity name.
6. `createFromApiItem()` hydrates: `fromArray()`, schema lookup, `validateExtraFields()`, `hydrateEntityFields()`, the identifier, `setMetadata()`, registration in the registry, then `setRelationships()`.

### Schema-driven hydration

A schema is an array holding `properties` entries of `{name, type, nullable, target}`. Hydration is strict in both directions: src/Helper/SchemaHelper.php`::assertAllowedFields()` throws on any field the schema does not declare (only `id` is whitelisted), and a non-nullable property arriving as `null` throws `ApiSchemaException::nonNullableNull()`. Values are cast by declared type — `int`, `float`, `bool`, `string`, `datetime` to `DateTimeImmutable`. Assignment tries a declared property first through reflection, then the generated setter, then gives up with `ApiSchemaException::propertyNotFound()`.

src/Common/AbstractApiEntity.php is deliberately thin: an `id`, plus `metadata`, `relationships`, `values` and `relationshipMap` arrays. Reads go through `__get()` and `__call()`, which look in `values`, then `relationshipMap`, then the relationships matched by name; `set*()` writes into `values`. An entity declaring no property still answers `$entity->getTitle()`.

### Relationships and stubs

`buildRelationshipsForEntity()` walks the schema for properties typed `relation` (one linked entity) or `collection` (many) and resolves each value:

- an inline array is hydrated by the target's own repository, recursively;
- a string found as a key in the item's `relationships` side-load is hydrated the same way;
- a bare string id becomes an src/Common/ApiEntityStub.php — `isStub() === true`, with a `targetName`.

src/Common/ApiEntityRegistry.php indexes hydrated entities by entity name and id. `registerStub()` swaps a stub immediately when the real entity is already known, otherwise queues it under a `WeakReference` to its owner; the next `registerEntity()` with that id calls `$owner->replaceRelationship($stub, $entity)` on everyone waiting. The weak reference keeps the registry from pinning entities in memory; the registry itself lives as long as the client.

Relationships are stored twice — a flat list and a `relationshipMap` keyed by property name — which is why `$article->author` returns one entity for a `relation` and an array for a `collection`.

### Errors

On top of php-api's `ApiException` (transport and HTTP status), this layer adds two:

- src/Exceptions/ApiEnvelopeException.php for a malformed or `type: error` envelope, carrying `getResponseCode()` and the full `getEnvelope()`;
- src/Exceptions/ApiSchemaException.php for anything that fails hydration, with a stable vocabulary exposed by `getErrorCode()` — `CODE_UNKNOWN_FIELD`, `CODE_PROPERTY_NOT_FOUND`, `CODE_NON_NULLABLE_NULL`, `CODE_INVALID_VALUE`, `CODE_INVALID_ITEM`, `CODE_UNKNOWN_RELATIONSHIP` — and named factories that always report the owning entity and field. A schema exception means the API contract drifted; the client is not meant to tolerate it.

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- wexample/php-api: >=5.0.0
- wexample/php-helpers: >=7.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
