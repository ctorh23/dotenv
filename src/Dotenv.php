<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv;

use Ctorh23\Dotenv\Exception\PathException;

/**
 * Processing environment variables in .env files.
 *
 * @author Stoyan Dimitrov
 */
final class Dotenv
{
    /**
     * The name of the file containing environment variables.
     */
    private string $envFile = '.env';

    /**
     * The directory containing .env files.
     */
    private string $envDir;

    public function __construct(
        private readonly string $path
    ) {
        //
    }

    /**
     * Loads variables declared in .env files.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    public function load(): void
    {
        $this->examinePath();
    }

    /**
     * Validate path existence and split it into dirname and filename.
     *
     * @throws \Ctorh23\Dotenv\Exception\PathException
     */
    private function examinePath(): void
    {
        if (is_file($this->path)) {
            $this->envFile = basename($this->path);
            $this->envDir = dirname($this->path);
        } elseif (is_dir($this->path)) {
            $this->envDir = $this->path;
        } else {
            throw new PathException($this->path);
        }
    }
}
