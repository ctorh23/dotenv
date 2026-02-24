# Dotenv - Environment Variables Manager

**Dotenv** parses files containing user-defined environment variables and populates the $\_ENV superglobal with the resulting values.

---

## Installation

**Dotenv** requires **PHP 8.3** or newer.

`composer require ctorh23/dotenv`

---

## Usage

### Basic Example

```php
use Ctorh23\Dotenv\Dotenv;

$dotenv = new Dotenv(__DIR__);
$dotenv->load();
```

In the code snippet above, you provide a directory containing your *.env* file. It may also contain *.env.local*, *.env-environment_specific*, and *.env-environment_specific.local* files. These will be parsed in the following order:
1. *.env*
2. *.env.local*
3. *.env-environment_specific*
4. *.env-environment_specific.local*

If the same variable is declared in multiple files, the last one will overwrite its definition in the previous files. *environment_specific* could be, for example, *'development'*, *'testing'*, *'production'*, etc. Which *environment_specific* file will be parsed depends on the special variable `APP_ENV`, which must be declared in either *.env* or *.env.local*. You can change the name of this variable:

```php
$dotenv->setAppEnvName('APPLICATION_ENVIRONMENT');
```

By default, **Dotenv** doesn't overwrite existing environment variables. This includes variables defined in the shell or by the web server. This means that if `EXAMPLE_VAR=example-value` has already been defined in the shell, it will not be overwritten, even if it is defined in one of the *.env* files. This behavior can be changed:

```php
$dotenv->setOverwrite(true);
```

You can also provide the path to the *.env* files using the `setPath` method instead of a constructor argument:

```php
$dotenv->setPath(__DIR__);
```

If your file is not named *.env*, but instead *my_vars*, for example, you can provide a path to that file:

```php
$dotenv = new Dotenv();
$dotenv->setPath(__DIR__ . '/my_vars')
    ->load();
```
Then **Dotenv** will parse the following files:
1. *my_vars*
2. *my_vars.local*
3. *my_vars-environment_specific*
4. *my_vars-environment_specific.local*

Finally, you can use environment variables in your application by calling the `getVar` static method:

```php
Dotenv::getVar('myvar');
```

### Alternative Usage

You can choose not to use the default logic. You can provide a single file to the **Dotenv** object:

```php
$dotenv = new Dotenv();
$vars = $dotenv->processFile(__DIR__ . '/custom-vars');
```

Or you can provide a list of files:

```php
$dotenv = new Dotenv();
$vars = $dotenv->processFileList([
    __DIR__ . '/custom-vars',
    __DIR__ . '/custom-vars.development',
]);
```

Then, you have to load the parsed variables into the `$_ENV` superglobal:

```php
$dotenv->writeVars($vars);
```

---

## Syntax of .env Files

Environment variable definitions must follow this format:

```
VAR=value

# This is a comment
ANOTHER_VAR="2nd value"
```

- The name of a variable must begin with an alphabetic character or an underscore, followed by alphanumeric characters or underscores.
- Any character is accepted for the value, but escape sequences ('\\n', '\\t', '\\r', etc.) must be preceded by a backslash.
- Lines starting with `#` are ignored and not parsed.
