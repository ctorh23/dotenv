<?php

declare(strict_types=1);

namespace Ctorh23\Dotenv\Exception;

/**
 * Thrown when an environment variable name or value does not match the required syntax.
 *
 * @author Stoyan Dimitrov
 */
final class EnvVarException extends \DomainException implements ExceptionInterface
{
    public function __construct(string $msg, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }

    public static function wrongDefinition(string $envFileLine): self
    {
        return new self(sprintf('The definition of the environment variable "%s" is missing equals-sign.', $envFileLine));
    }

    public static function wrongName(string $varName): self
    {
        return new self(sprintf('Not valid environment variable name "%s"! It must begin with an alphabetic character or an underscore, followed by alphanumeric characters or underscores.', $varName));
    }

    public static function wrongValue(string $varVal): self
    {
        return new self(sprintf('Not valid environment variable value "%s"! Escape sequences must be preceded by a backslash.', $varVal));
    }
}
