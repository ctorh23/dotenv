<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv;

use Ctorh23\Dotenv\Exception\PathException;
use Ctorh23\Dotenv\Exception\EnvVarException;

/**
 * Processing environment variables in .env files.
 *
 * @author Stoyan Dimitrov
 */
final class Dotenv implements DotenvInterface
{
    /**
     * The regex pattern for each line in .env file.
     */
    private const VAR_PATTERN = '/^([a-zA-Z_][a-zA-Z0-9_]+)=([^\'\"\`\r\n\t\f\v\s]+)$/';

    /**
     * The regex pattern for a variable name.
     */
    private const VAR_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]+$/';

    /**
     * The path to the directory containing .env files or the file itself.
     */
    private string $path;

    /**
     * The name of the file containing environment variables.
     */
    private string $envFile = '.env';

    /**
     * The directory containing .env files.
     */
    private string $envDir;

    /**
     * The name of variable used for defining application environment (e.g. development, test, production)
     */
    private string $appEnvName = 'APP_ENV';

    /**
     * A flag used to disable/enable overwriting of existing (externally-defined) environment variables.
     */
    private bool $overwrite = false;

    public function __construct(string $path = '')
    {
        $this->setPath($path);
    }

    /**
     * A setter method. The path can be set only once.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    public function setPath(string $path): self
    {
        if (isset($this->path)) {
            throw PathException::alreadySet();
        }

        if (\strlen(\trim($path))) {
            $this->path = \trim($path);
        }

        return $this;
    }

    /**
     * A setter method.
     *
     * @throws \Ctorh23\Dotenv\Exception\EnvVarException
     */
    public function setAppEnvName(string $appEnvName): self
    {
        if (!$this->validateVarName($appEnvName)) {
            throw EnvVarException::wrongName($appEnvName);
        }

        $this->appEnvName = $appEnvName;

        return $this;
    }

    /**
     * A setter method.
     */
    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Loads variables declared in .env files.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    public function load(): void
    {
        $this->examinePath();
        $vars = $this->processFileList($this->prepareFileList());

        $appEnv = $this->fetchAppEnv($vars);
        if (\strlen($appEnv)) {
            $varsEnvSpec = $this->processFileList($this->prepareFileList($appEnv));
            $vars = \array_merge($vars, $varsEnvSpec);
        }

        $this->writeVars($vars);
    }

    /**
     * Passes each item of a $fileList parameter to the .env parser and merges results.
     *
     * @throws \Ctorh23\Dotenv\Exception\EnvVarException
     * @throws \Ctorh23\Dotenv\Exception\PathException
     *
     * @param array<string> $fileList
     *
     * @return array<string, string>
     */
    public function processFileList(array $fileList): array
    {
        $vars = [];
        foreach ($fileList as $fl) {
            $fileVars = $this->processFile($fl);
            if (\count($fileVars)) {
                $vars = \array_merge($vars, $fileVars);
            }
        }

        return $vars;
    }

    /**
     * This parses .env files
     *
     * @throws \Ctorh23\Dotenv\Exception\EnvVarException
     * @throws \Ctorh23\Dotenv\Exception\PathException
     *
     * @return array<string, string>
     */
    public function processFile(string $file): array
    {
        if (!\is_file($file) || !\is_readable($file)) {
            throw PathException::notAccessible($file);
        }

        $vars = [];
        $lines = \file($file, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        if (\is_array($lines)) {
            foreach ($lines as $ln) {
                if (!\preg_match(self::VAR_PATTERN, $ln, $matches) || \count($matches) < 3) {
                    throw EnvVarException::wrongDefinition($ln);
                }
                $vars[$matches[1]] = $matches[2];
            }
        }

        return $vars;
    }

    /**
     * Sets variables from the array as environment variables.
     *
     * @param array<string, string> $vars
     */
    public function writeVars(array $vars): void
    {
        foreach ($vars as $varName => $varVal) {
            if (!isset($_ENV[$varName]) || $this->overwrite) {
                $_ENV[$varName] = $varVal;
            }
        }
    }

    /**
     * Validator for an environment variable name.
     */
    public function validateVarName(string $varName): bool
    {
        return \preg_match(self::VAR_NAME_PATTERN, $varName) ? true : false;
    }

    /**
     * Validate path existence and split it into dirname and filename.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    private function examinePath(): void
    {
        if (!isset($this->path) || !\strlen($this->path)) {
            throw PathException::notSet();
        }

        if (\is_file($this->path) && \is_readable($this->path)) {
            $this->envFile = \basename($this->path);
            $this->envDir = \dirname($this->path);
        } elseif (\is_dir($this->path) && \is_readable($this->path)) {
            $this->envDir = $this->path;
        } else {
            throw PathException::notAccessible($this->path);
        }

        $this->envDir = \rtrim($this->envDir, '/') . '/';
    }

    /**
     * Prepare a list of .env files
     *
     * @return array<string>
     */
    private function prepareFileList(string $envName = ''): array
    {
        $fileList = [];

        $files = [
            $this->envDir . $this->envFile . ($envName ? '-' . $envName : ''),
            $this->envDir . $this->envFile . ($envName ? '-' . $envName : '') . '.local',
        ];

        foreach ($files as $f) {
            if (\is_readable($f)) {
                $fileList[] = $f;
            }
        }

        return $fileList;
    }

    /**
     * Defines application environment
     *
     * @param array<string, string> $vars
     */
    private function fetchAppEnv(array $vars): string
    {
        $appEnv = $_ENV[$this->appEnvName] ?? '';

        if (($appEnv === '' || $this->overwrite) && isset($vars[$this->appEnvName])) {
            $appEnv = $vars[$this->appEnvName];
        }

        return \strval($appEnv);
    }
}
