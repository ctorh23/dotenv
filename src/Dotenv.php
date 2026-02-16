<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv;

use Ctorh23\Dotenv\Exception\PathException;
use Ctorh23\Dotenv\Exception\SyntaxException;

/**
 * Processing environment variables in .env files.
 *
 * @author Stoyan Dimitrov
 */
final class Dotenv
{
    /**
     * The regex pattern for each line in .env file.
     */
    private const VAR_PATTERN = '/^([a-zA-Z_][a-zA-Z0-9_]+)=([^\'\"\`\r\n\t\f\v\s]+)$/';

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

    public function __construct(string $path = '')
    {
        $this->setPath($path);
    }

    /**
     * A setter. The path can be set only once.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    public function setPath(string $path): void
    {
        if (isset($this->path)) {
            throw PathException::alreadySet();
        }

        if (\strlen(\trim($path))) {
            $this->path = \trim($path);
        }
    }

    /**
     * Loads variables declared in .env files.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    public function load(): void
    {
        $this->examinePath();
        $files = $this->prepareFileList();
    }

    /**
     * This parses .env files
     *
     * @throws \Ctorh23\Dotenv\Exception\SyntaxException
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
        if (\is_array($lines) && \count($lines)) {
            foreach ($lines as $ln) {
                if (!\preg_match(self::VAR_PATTERN, $ln, $matches) || \count($matches) < 3) {
                    throw SyntaxException::incorrectVarDefinition($ln);
                }
                $vars[$matches[1]] = $matches[2];
            }
        }

        return $vars;
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
            $this->envDir . $this->envFile . $envName,
            $this->envDir . $this->envFile . $envName . '.local',
        ];

        foreach ($files as $f) {
            if (\is_readable($f)) {
                $fileList[] = $f;
            }
        }

        return $fileList;
    }
}
