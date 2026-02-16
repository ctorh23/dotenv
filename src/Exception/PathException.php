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
    public function __construct(string $msg, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }

    public static function notAccessible(string $path): self
    {
        return new self(sprintf('"%s" does not exist or is not accessible!', $path));
    }

    public static function notSet(): self
    {
        return new self('Path not set!');
    }

    public static function alreadySet(): self
    {
        return new self('Path can not be overwritten one set!');
    }
}
