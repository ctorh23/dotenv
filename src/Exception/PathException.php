<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv\Exception;

/**
 * Thrown when a file or directory does not exist or is not accessible.
 *
 * @author Stoyan Dimitrov
 */
class PathException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $path, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" does not exist or is not accessible!', $path), $code, $previous);
    }
}
