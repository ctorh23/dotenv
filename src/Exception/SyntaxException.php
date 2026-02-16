<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv\Exception;

/**
 * Thrown when a file or directory does not exist or is not accessible.
 *
 * @author Stoyan Dimitrov
 */
class SyntaxException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $msg, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }

    public static function incorrectVarDefinition(string $envFileLine): self
    {
        return new self(sprintf('The environment variable is not defined correctly "%s"!', $envFileLine));
    }
}
