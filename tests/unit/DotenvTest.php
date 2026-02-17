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

    public function testProcessFileListDifferentVars(): void
    {
        $sut = new DotEnv();
        $fileList = [
            \FIXTURES_PATH . '/envfiles/.env-list-base',
            \FIXTURES_PATH . '/envfiles/.env-list-different',
        ];
        $vars = $sut->processFileList($fileList);

        $arrExpected = [
            'DB_HOST' => 'example.host',
            'DB_PORT' => '3306',
            'DB_USER' => 'appdbuser',
            'DB_PASS' => 'appdbpass',
            'DB_NAME' => 'exampledb',
            'DB_ENCODING' => 'utf-8',
        ];

        $this->assertEquals($arrExpected, $vars);
    }

    public function testProcessFileListOverlapVars(): void
    {
        $sut = new DotEnv();
        $fileList = [
            \FIXTURES_PATH . '/envfiles/.env-list-base',
            \FIXTURES_PATH . '/envfiles/.env-list-overlap',
        ];
        $vars = $sut->processFileList($fileList);

        $arrExpected = [
            'DB_HOST' => 'example.host',
            'DB_USER' => 'appdbuser',
            'DB_PORT' => '5432',
            'DB_TIMEOUT' => '5',
            'DB_PASS' => 'appdbsecret',
        ];

        $this->assertEquals($arrExpected, $vars);
    }

    public function testWriteVarsNoOverwrite(): void
    {
        $_ENV['AWS_ACCESS_KEY_ID'] = 'acme';
        $_ENV['AWS_SECRET_ACCESS_KEY'] = 'verySecretPhrase';

        $vars = [
            'AWS_SECRET_ACCESS_KEY' => 'shouldNotBeUsed',
            'AWS_DEFAULT_REGION' => 'eu-west-1',

        ];

        $sut = new DotEnv();
        $sut->writeVars($vars);
        $this->assertArrayHasKey('AWS_DEFAULT_REGION', $_ENV);
        $this->assertArrayHasKey('AWS_SECRET_ACCESS_KEY', $_ENV);
        $this->assertEquals($_ENV['AWS_DEFAULT_REGION'], 'eu-west-1');
        $this->assertEquals($_ENV['AWS_SECRET_ACCESS_KEY'], 'verySecretPhrase');
    }
}
