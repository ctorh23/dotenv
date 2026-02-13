<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv\Tests;

use Ctorh23\Dotenv\Dotenv;
use Ctorh23\Dotenv\Exception\PathException;
use PHPUnit\Framework\TestCase;

final class DotenvTest extends TestCase
{
    public function testWrongPathRisesException(): void
    {
        $sut = new DotEnv('/not/existing');
        $this->expectException(PathException::class);
        $sut->load();
    }
}
