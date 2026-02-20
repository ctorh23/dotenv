<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv\Tests;

use Ctorh23\Dotenv\Dotenv;
use Ctorh23\Dotenv\Exception\PathException;
use Ctorh23\Dotenv\Exception\EnvVarException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\BackupGlobals;

final class DotenvTest extends TestCase
{
    /**
     * @covers DotEnv::load()
     * @covers DotEnv::examinePath()
     */
    #[DataProvider('provideWrongPath')]
    public function testWrongPathRisesException(string $path): void
    {
        $sut = new DotEnv($path);
        $this->expectException(PathException::class);
        $sut->load();
    }

    public static function provideWrongPath(): array
    {
        return [
            ['/not/existing'],
            [''],
        ];
    }

    /**
     * @covers DotEnv::processFile()
     */
    public function testProcessFileWithNoFile(): void
    {
        $sut = new DotEnv();
        $this->expectException(PathException::class);
        $sut->processFile(\FIXTURES_PATH);
    }

    /**
     * @covers DotEnv::processFile()
     */
    public function testProcessFileWithWrongDefinition(): void
    {
        $sut = new DotEnv();
        $this->expectException(EnvVarException::class);
        $sut->processFile(\FIXTURES_PATH . '/envfiles/.wrong-syntax');
    }

    /**
     * @covers DotEnv::processFile()
     */
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

    /**
     * @covers DotEnv::processFileList()
     */
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

    /**
     * @covers DotEnv::processFileList()
     */
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

    /**
     * @covers DotEnv::writeVars()
     */
    #[BackupGlobals(true)]
    public function testWriteVarsOverwriteDisabled(): void
    {
        $_ENV['AWS_ACCESS_KEY_ID'] = 'acme';
        $_ENV['AWS_SECRET_ACCESS_KEY'] = 'verySecretPhrase';

        $vars = [
            'AWS_SECRET_ACCESS_KEY' => 'shouldNotBeUsed',
            'AWS_DEFAULT_REGION' => 'eu-west-1',

        ];

        (new DotEnv())
            ->writeVars($vars);
        $this->assertArrayHasKey('AWS_DEFAULT_REGION', $_ENV);
        $this->assertArrayHasKey('AWS_SECRET_ACCESS_KEY', $_ENV);
        $this->assertEquals($_ENV['AWS_DEFAULT_REGION'], 'eu-west-1');
        $this->assertEquals($_ENV['AWS_SECRET_ACCESS_KEY'], 'verySecretPhrase');
    }

    /**
     * @covers DotEnv::setOverwrite()
     * @covers DotEnv::writeVars()
     */
    #[BackupGlobals(true)]
    public function testWriteVarsOverwriteEnabled(): void
    {
        $_ENV['AWS_ACCESS_KEY_ID'] = 'acme';
        $_ENV['AWS_SECRET_ACCESS_KEY'] = 'shouldNotBeUsed';

        $vars = [
            'AWS_SECRET_ACCESS_KEY' => 'verySecretPhrase',
            'AWS_DEFAULT_REGION' => 'eu-west-1',

        ];

        (new DotEnv())
            ->setOverwrite(true)
            ->writeVars($vars);
        $this->assertArrayHasKey('AWS_DEFAULT_REGION', $_ENV);
        $this->assertArrayHasKey('AWS_SECRET_ACCESS_KEY', $_ENV);
        $this->assertEquals($_ENV['AWS_DEFAULT_REGION'], 'eu-west-1');
        $this->assertEquals($_ENV['AWS_SECRET_ACCESS_KEY'], 'verySecretPhrase');
    }

    /**
     * @covers DotEnv::setAppEnvName()
     * @covers DotEnv::validateVarName()
     */
    #[DataProvider('provideWrongVarName')]
    public function testSetAppEnvNameNotValidRisesException(string $varName): void
    {
        $sut = new DotEnv();
        $this->expectException(EnvVarException::class);
        $sut->setAppEnvName($varName);
    }

    public static function provideWrongVarName(): array
    {
        return [
            ['1st_ENV'],
            ['App-Env'],
            ['AppEnv!'],
        ];
    }

    /**
     * @covers DotEnv::load()
     * @covers DotEnv::examinePath()
     * @covers DotEnv::prepareFileList()
     * @covers DotEnv::fetchAppEnv()
     */
    #[DataProvider('provideEnvFilesDefaultAppEnvName')]
    #[BackupGlobals(true)]
    public function testLoadSuccessWithDefaultAppEnvName(string $path): void
    {
        (new DotEnv(\FIXTURES_PATH . '/' . $path))
            ->load();

        $this->assertArrayHasKey('DB_HOST', $_ENV);
        $this->assertArrayHasKey('DB_PORT', $_ENV);
        $this->assertArrayHasKey('DB_USER', $_ENV);
        $this->assertArrayHasKey('DB_PASS', $_ENV);
        $this->assertEquals($_ENV['DB_HOST'], 'example.host');
        $this->assertEquals($_ENV['DB_PORT'], '3306');
        $this->assertEquals($_ENV['DB_USER'], 'appdb_user');
        $this->assertEquals($_ENV['DB_PASS'], 'appdb-pass');
    }

    public static function provideEnvFilesDefaultAppEnvName(): array
    {
        return [
            ['env_only'],
            ['envlocal_only'],
            ['env_and_envlocal'],
            ['env_and_envlocal_and_spec_and_speclocal'],
            ['env_and_envlocal_and_not-matching-spec'],
            ['custom_filename/my-app.vars'],
        ];
    }

    /**
     * @covers DotEnv::setPath()
     * @covers DotEnv::setAppEnvName()
     * @covers DotEnv::load()
     * @covers DotEnv::examinePath()
     * @covers DotEnv::prepareFileList()
     * @covers DotEnv::fetchAppEnv()
     */
    #[BackupGlobals(true)]
    public function testLoadSuccessWithCustomAppEnvName(): void
    {
        (new DotEnv())
            ->setPath(\FIXTURES_PATH . '/env_and_envlocal_and_spec_and_speclocal')
            ->setAppEnvName('APPLICATION_ENVIRONMENT')
            ->load();

        $this->assertArrayHasKey('DB_HOST', $_ENV);
        $this->assertArrayHasKey('DB_PORT', $_ENV);
        $this->assertArrayHasKey('DB_USER', $_ENV);
        $this->assertArrayHasKey('DB_PASS', $_ENV);
        $this->assertEquals($_ENV['DB_HOST'], 'example.host');
        $this->assertEquals($_ENV['DB_PORT'], '3306');
        $this->assertEquals($_ENV['DB_USER'], 'appdb_admin');
        $this->assertEquals($_ENV['DB_PASS'], 'appdb-password');
    }
}
