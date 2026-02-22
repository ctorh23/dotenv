<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv;

/**
 * Processing environment variables in .env files.
 *
 * @author Stoyan Dimitrov
 */
interface DotenvInterface
{
    /**
     * A setter method. The path can be set only once.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    public function setPath(string $path): self;

    /**
     * A setter method.
     *
     * @throws \Ctorh23\Dotenv\Exception\EnvVarException
     */
    public function setAppEnvName(string $appEnvName): self;

    /**
     * A setter method.
     */
    public function setOverwrite(bool $overwrite): self;

    /**
     * Loads variables declared in .env files.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    public function load(): void;

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
    public function processFileList(array $fileList): array;

    /**
     * This parses .env files
     *
     * @throws \Ctorh23\Dotenv\Exception\EnvVarException
     * @throws \Ctorh23\Dotenv\Exception\PathException
     *
     * @return array<string, string>
     */
    public function processFile(string $file): array;

    /**
     * Sets variables from the array as environment variables.
     *
     * @param array<string, string> $vars
     */
    public function writeVars(array $vars): void;
}
