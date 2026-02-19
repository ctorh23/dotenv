<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv\Exception;

/**
 * Thrown when an environment variable name or value does not match the required syntax.
 *
 * @author Stoyan Dimitrov
 */
final class SyntaxException extends \DomainException implements ExceptionInterface
{
    public function __construct(string $msg, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }

    public static function wrongDefinition(string $envFileLine): self
    {
        return new self(sprintf('The environment variable is not defined correctly "%s"!', $envFileLine));
    }

    public static function wrongName(string $varName): self
    {
        return new self(sprintf('Not valid environment variable name "%s"!', $varName));
    }
}
