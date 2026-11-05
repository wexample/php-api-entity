<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Common;

use GuzzleHttp\ClientInterface;
use Wexample\PhpApi\Common\AbstractApiClient;
use Wexample\PhpApi\Common\ClientOptions;

abstract class AbstractApiEntitiesClient extends AbstractApiClient
{
    private ApiEntityManager $entityManager;
    private ApiEntityRegistry $entityRegistry;
    private ?array $entitySchemas = null;

    public function __construct(
        string $baseUrl,
        ?string $apiKey = null,
        ?ClientInterface $httpClient = null,
        array $defaultHeaders = [],
        ?ClientOptions $options = null,
    ) {
        parent::__construct($baseUrl, $apiKey, $httpClient, $defaultHeaders, $options);

        $this->entityManager = new ApiEntityManager($this, $this->getRepositoryClasses());
        $this->entityRegistry = new ApiEntityRegistry();
    }

    /**
     * @return class-string<AbstractApiRepository>[]
     */
    abstract protected function getRepositoryClasses(): array;

    /**
     * Entity schemas exported by the remote symfony-api, keyed by entity name;
     * repositories validate and hydrate items against them. Read once from
     * getEntitySchemaDirectories(), by default.
     *
     * @return array<string, array>
     */
    public function getEntitySchemas(): array
    {
        return $this->entitySchemas ??= (new EntitySchemaLoader())->load($this->getEntitySchemaDirectories());
    }

    /**
     * Where the schemas lie, a later directory's merged over an earlier
     * one's: a client extending another adds its own after its parent's.
     *
     * @return list<string>
     */
    protected function getEntitySchemaDirectories(): array
    {
        return [];
    }

    /**
     * The repositories of a client package: every `*Repository` class of
     * `<srcDir>/Repository`, in `$repositoryNamespace`.
     *
     * @return list<class-string<AbstractApiRepository>>
     */
    protected function discoverRepositoryClasses(
        string $srcDir,
        string $repositoryNamespace
    ): array {
        $classes = [];
        $files = glob(rtrim($srcDir, '/\\') . '/Repository/*Repository.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $className = $repositoryNamespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($className)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    public function getEntityManager(): ApiEntityManager
    {
        return $this->entityManager;
    }

    public function getEntityRegistry(): ApiEntityRegistry
    {
        return $this->entityRegistry;
    }

    /**
     * @param string|class-string<AbstractApiEntity> $entity
     */
    public function getRepository(string $entity): AbstractApiRepository
    {
        return $this->entityManager->get($entity);
    }

    public function buildEntityEntrypoint(AbstractApiEntity|string $abstractApiEntity, string $path): string
    {
        return $abstractApiEntity::getSnakeShortClassName() . '/' . $path;
    }
}
