<?php

declare(strict_types=1);

namespace Wexample\PhpApiEntity\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Wexample\PhpApiEntity\Helper\SchemaHelper;

class SchemaHelperTest extends TestCase
{
    public function testATextReadingNullStaysText(): void
    {
        $this->assertSame('null', SchemaHelper::normalizeValue('null', 'string', false, 'demo', 'title'));
        $this->assertSame('NULL', SchemaHelper::normalizeValue('NULL', 'string', true, 'demo', 'title'));
        $this->assertNull(SchemaHelper::normalizeValue(null, 'string', true, 'demo', 'title'));
    }

    public function testANullStringStillEmptiesAnotherType(): void
    {
        $this->assertNull(SchemaHelper::normalizeValue('null', 'int', true, 'demo', 'count'));
        $this->assertNull(SchemaHelper::normalizeValue('null', 'datetime', true, 'demo', 'createdAt'));
    }

    public function testADateKeepsItsMicroseconds(): void
    {
        $date = new \DateTime('2024-02-29 13:45:10.123456', new \DateTimeZone('Europe/Paris'));

        $normalized = SchemaHelper::normalizeValue($date, 'datetime', false, 'demo', 'createdAt');

        $this->assertInstanceOf(\DateTimeImmutable::class, $normalized);
        $this->assertSame('2024-02-29 13:45:10.123456 Europe/Paris', $normalized->format('Y-m-d H:i:s.u e'));
    }
}
