<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Wexample\PhpApiEntity\Common\ApiEntityStub;
use Wexample\PhpApiEntity\Exceptions\ApiEnvelopeException;
use Wexample\PhpApiEntity\Exceptions\ApiSchemaException;
use Wexample\PhpApiEntity\Tests\Fixtures\DemoClient;
use Wexample\PhpApiEntity\Tests\Fixtures\DemoProject;
use Wexample\PhpApiEntity\Tests\Fixtures\DemoUser;

class AbstractApiRepositoryTest extends TestCase
{
    public function testFetchListUnwrapsTheEnvelopeAndHydratesItems(): void
    {
        $client = $this->createClient([
            $this->envelope(['items' => [
                $this->projectItem('p1', ['title' => 'First', 'archived' => 'false']),
                $this->projectItem('p2', ['title' => 'Second', 'archived' => true]),
            ]]),
        ]);

        $projects = $client->getRepository(DemoProject::class)->fetchList();

        $this->assertCount(2, $projects);
        $this->assertInstanceOf(DemoProject::class, $projects[0]);
        $this->assertSame('p1', $projects[0]->getId());
        $this->assertSame('First', $projects[0]->getTitle());
        $this->assertFalse($projects[0]->getArchived());
        $this->assertTrue($projects[1]->getArchived());
    }

    public function testInlineRelationIsHydratedAndIdOnlyRelationBecomesAStub(): void
    {
        $client = $this->createClient([
            $this->envelope(['items' => [
                $this->projectItem('p1', [
                    'title' => 'Inline',
                    'owner' => ['type' => 'demoUser', 'entity' => ['id' => 'u1', 'name' => 'Ada']],
                ]),
                $this->projectItem('p2', ['title' => 'Stub', 'owner' => 'u2']),
            ]]),
        ]);

        [$inline, $stubbed] = $client->getRepository('demoProject')->fetchList();

        $this->assertInstanceOf(DemoUser::class, $inline->getOwner());
        $this->assertSame('Ada', $inline->getOwner()->getName());
        $this->assertInstanceOf(ApiEntityStub::class, $stubbed->getOwner());
        $this->assertSame('u2', $stubbed->getOwner()->getId());
    }

    public function testFetchHydratesASingleItem(): void
    {
        $client = $this->createClient([
            $this->envelope($this->projectItem('p1', ['title' => 'Alone'])),
        ]);

        $project = $client->getRepository(DemoProject::class)->fetch('p1');

        $this->assertSame('Alone', $project->getTitle());
    }

    public function testErrorEnvelopeRaisesItsMessage(): void
    {
        $client = $this->createClient([
            new Response(200, [], json_encode(['type' => 'error', 'code' => 403, 'message' => 'ERR_FORBIDDEN'])),
        ]);

        $this->expectException(ApiEnvelopeException::class);
        $this->expectExceptionMessage('ERR_FORBIDDEN');

        $client->getRepository(DemoProject::class)->fetchList();
    }

    public function testFieldOutsideTheSchemaIsRejected(): void
    {
        $client = $this->createClient([
            $this->envelope(['items' => [$this->projectItem('p1', ['unexpected' => 'x'])]]),
        ]);

        $this->expectException(ApiSchemaException::class);

        $client->getRepository(DemoProject::class)->fetchList();
    }

    public function testItemOfAnotherTypeIsRejected(): void
    {
        $client = $this->createClient([
            $this->envelope(['type' => 'demoUser', 'entity' => ['id' => 'u1', 'name' => 'Ada']]),
        ]);

        $this->expectException(ApiSchemaException::class);

        $client->getRepository(DemoProject::class)->fetch('u1');
    }

    /**
     * @param array<int, Response> $queue
     */
    private function createClient(array $queue): DemoClient
    {
        return new DemoClient(
            'https://remote.test',
            httpClient: new GuzzleClient([
                'base_uri' => 'https://remote.test/',
                'handler' => HandlerStack::create(new MockHandler($queue)),
            ]),
        );
    }

    private function envelope(array $data): Response
    {
        return new Response(200, [], json_encode(['type' => 'success', 'code' => 200, 'data' => $data]));
    }

    private function projectItem(string $id, array $fields): array
    {
        return ['type' => 'demoProject', 'entity' => ['id' => $id] + $fields];
    }
}
