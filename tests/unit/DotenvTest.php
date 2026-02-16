<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv\Tests;

use Ctorh23\Dotenv\Dotenv;
use Ctorh23\Dotenv\Exception\PathException;
use Ctorh23\Dotenv\Exception\SyntaxException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DotenvTest extends TestCase
{
    #[DataProvider('wrongPathProvider')]
    public function testWrongPathRisesException(string $path): void
    {
        $sut = new DotEnv($path);
        $this->expectException(PathException::class);
        $sut->load();
    }

    public static function wrongPathProvider(): array
    {
        return [
            ['/not/existing'],
            [''],
        ];
    }

    public function testSetingPathManyTimes(): void
    {
        $sut = new DotEnv('/dummy/path');
        $this->expectException(PathException::class);
        $sut->setPath('/new/path');
    }

    public function testProcessFileWithNoFile(): void
    {
        $sut = new DotEnv();
        $this->expectException(PathException::class);
        $sut->processFile(\FIXTURES_PATH);
    }

    public function testProcessFileWithWrongDefinition(): void
    {
        $sut = new DotEnv();
        $this->expectException(SyntaxException::class);
        $sut->processFile(\FIXTURES_PATH . '/envfiles/.wrong-syntax');
    }

    public function testProcessFileWithCorrectDefinition(): void
    {
        $sut = new DotEnv();
        $vars = $sut->processFile(\FIXTURES_PATH . '/envfiles/.correct-syntax');

        $arrExpected = [
            'varOne' => 'First',
            'var2' => 'Second',
            'varThree' => '3rd',
        ];

        $this->assertEquals($arrExpected, $vars);
    }
}
