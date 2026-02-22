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
     * @covers Dotenv::load()
     * @covers Dotenv::examinePath()
     */
    #[DataProvider('provideWrongPath')]
    public function testWrongPathRisesException(string $path): void
    {
        $sut = new Dotenv($path);
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
     * @covers Dotenv::processFile()
     */
    public function testProcessFileWithNoFile(): void
    {
        $sut = new Dotenv();
        $this->expectException(PathException::class);
        $sut->processFile(\FIXTURES_PATH);
    }

    /**
     * @covers Dotenv::processFile()
     * @covers Dotenv::validateVarName()
     * @covers Dotenv::validateVarValue()
     */
    public function testProcessFileWithWrongDefinition(): void
    {
        $sut = new Dotenv();
        $this->expectException(EnvVarException::class);
        $sut->processFile(\FIXTURES_PATH . '/envfiles/.wrong-syntax');
    }

    /**
     * @covers Dotenv::processFile()
     * @covers Dotenv::validateVarName()
     * @covers Dotenv::validateVarValue()
     */
    public function testProcessFileWithCorrectDefinition(): void
    {
        $sut = new Dotenv();
        $vars = $sut->processFile(\FIXTURES_PATH . '/envfiles/.correct-syntax');

        $arrExpected = [
            'varOne' => 'First',
            'var2' => '',
            'VarThree' => '3rd',
            '_var4' => '"test "',
            'var5' => '!@#$%^&*()_+=-`~.,\'"?<>/|\\',
            'var_digit' => '3',
        ];

        $this->assertEquals($arrExpected, $vars);
    }

    /**
     * @covers Dotenv::processFileList()
     */
    public function testProcessFileListDifferentVars(): void
    {
        $sut = new Dotenv();
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
     * @covers Dotenv::processFileList()
     */
    public function testProcessFileListOverlapVars(): void
    {
        $sut = new Dotenv();
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
     * @covers Dotenv::writeVars()
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

        (new Dotenv())
            ->writeVars($vars);
        $this->assertArrayHasKey('AWS_DEFAULT_REGION', $_ENV);
        $this->assertArrayHasKey('AWS_SECRET_ACCESS_KEY', $_ENV);
        $this->assertEquals($_ENV['AWS_DEFAULT_REGION'], 'eu-west-1');
        $this->assertEquals($_ENV['AWS_SECRET_ACCESS_KEY'], 'verySecretPhrase');
    }

    /**
     * @covers Dotenv::setOverwrite()
     * @covers Dotenv::writeVars()
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

        (new Dotenv())
            ->setOverwrite(true)
            ->writeVars($vars);
        $this->assertArrayHasKey('AWS_DEFAULT_REGION', $_ENV);
        $this->assertArrayHasKey('AWS_SECRET_ACCESS_KEY', $_ENV);
        $this->assertEquals($_ENV['AWS_DEFAULT_REGION'], 'eu-west-1');
        $this->assertEquals($_ENV['AWS_SECRET_ACCESS_KEY'], 'verySecretPhrase');
    }

    /**
     * @covers Dotenv::setAppEnvName()
     * @covers Dotenv::validateVarName()
     */
    #[DataProvider('provideWrongEnvVarName')]
    public function testSetAppEnvNameNotValidRisesException(string $varName): void
    {
        $sut = new Dotenv();
        $this->expectException(EnvVarException::class);
        $sut->setAppEnvName($varName);
    }

    public static function provideWrongEnvVarName(): array
    {
        return [
            ['1st_ENV'],
            ['App-Env'],
            ['AppEnv!'],
        ];
    }

    /**
     * @covers Dotenv::load()
     * @covers Dotenv::examinePath()
     * @covers Dotenv::prepareFileList()
     * @covers Dotenv::fetchAppEnv()
     */
    #[DataProvider('provideEnvFilesDefaultAppEnvName')]
    #[BackupGlobals(true)]
    public function testLoadSuccessWithDefaultAppEnvName(string $path): void
    {
        (new Dotenv(\FIXTURES_PATH . '/' . $path))
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
     * @covers Dotenv::setPath()
     * @covers Dotenv::setAppEnvName()
     * @covers Dotenv::load()
     * @covers Dotenv::examinePath()
     * @covers Dotenv::prepareFileList()
     * @covers Dotenv::fetchAppEnv()
     */
    #[BackupGlobals(true)]
    public function testLoadSuccessWithCustomAppEnvName(): void
    {
        (new Dotenv())
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

    /**
     * @covers Dotenv::getVar()
     * @covers Dotenv::writeVars()
     */
    #[BackupGlobals(true)]
    public function testGetVar(): void
    {
        (new Dotenv())
            ->writeVars(['my_var' => 1]);
        $this->assertSame(Dotenv::getVar('my_var'), '1');
        $this->assertSame(Dotenv::getVar('no_var'), '');
    }
}
