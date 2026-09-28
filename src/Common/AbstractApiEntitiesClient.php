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
     * repositories validate and hydrate items against them.
     *
     * @return array<string, array>
     */
    abstract public function getEntitySchemas(): array;

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
