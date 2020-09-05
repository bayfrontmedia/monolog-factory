## Monolog Factory

An easy to use library which allows configuration-based creation of [Monolog](https://github.com/Seldaek/monolog) objects.

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](https://github.com/bayfrontmedia/php-array-helpers/blob/master/LICENSE).

## Author

John Robinson, [Bayfront Media](https://www.bayfrontmedia.com)

## Requirements

* PHP >= 7.1.0

## Installation

```
composer require bayfrontmedia/monolog-factory
```

## Usage

**NOTE:** All exceptions thrown by Monolog Factory extend `Bayfront\MonologFactory\Exceptions\LoggerException`, so you can choose to catch exceptions as narrowly or broadly as you like. 

Monolog Factory exists in order to "bootstrap" Monolog channels by means of a configuration array.
It allows for the creation of multiple channels, each with their own handlers, formatters and processors.

In some cases, you may still need to interact with the `Monolog\Logger` object directly, and Monolog Factory allows you to do that via the [getChannel](#getchannel) method.

### Configuration array

**Array structure:**

```
$config = [
    'App' => [ // Channel name
        'default' => true, // One channel must be marked as "default"
        'enabled' => true, // Channels can be enabled/disabled
        'handlers' => [ // Each channel can have multiple handlers
            'RotatingFileHandler' => [ // Class name in Monolog\Handler namespace
                'params' => [ // Array of parameters to pass to the handler's constructor
                    'filename' => __DIR__ . '/logs/app.log',
                    'maxFiles' => 30,
                    'level' => 'WARNING'
                ],
                'formatter' => [ // Optional formatter for this handler
                    'name' => 'LineFormatter', // Class name in Monolog\Formatter namespace
                    'params' => [ // Array of parameters to pass to the formatter's constructor
                        'output' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                        'dateformat' => 'Y-m-d H:i:s T'
                    ]
                ]
            ]
        ],
        'processors' => [ // Optional processors for this channel
            'IntrospectionProcessor' => [ // Class name in Monolog\Processor namespace
                'params' => [ // Array of parameters to pass to the processor's constructor
                    'level' => 'ERROR'
                ]
            ],
            'WebProcessor' => [
            ]
        ]
    ]
];
```

**Example:**

```
use Bayfront\MonologFactory\Exceptions\LoggerException;
use Bayfront\MonologFactory\LoggerFactory;

$config = [
    'App' => [
        'default' => true,
        'enabled' => true,
        'handlers' => [
            'RotatingFileHandler' => [
                'params' => [
                    'filename' => __DIR__ . '/logs/app.log',
                    'maxFiles' => 30,
                    'level' => 'WARNING'
                ],
                'formatter' => [
                    'name' => 'LineFormatter',
                    'params' => [
                        'output' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                        'dateformat' => 'Y-m-d H:i:s T'
                    ]
                ]
            ]
        ],
        'processors' => [
            'IntrospectionProcessor' => [
                'params' => [
                    'level' => 'ERROR'
                ]
            ]
        ]
    ],
    'Dev' => [
        'enabled' => true,
        'handlers' => [
            'BrowserConsoleHandler' => [
                'params' => [
                    'level' => 'DEBUG'
                ]
            ]
        ]
    ]
];

try {

    $logger = new LoggerFactory($config);

} catch (LoggerException $e) {
    die($e->getMessage());
}
```

### Public methods

- [getCurrentChannel](#getcurrentchannel)
- [getDefaultChannel](#getdefaultchannel)
- [addChannel](#addchannel)
- [getChannel](#getchannel)
- [isChannel](#ischannel)
- [channel](#channel)

**Logging events**

- [emergency](#emergency)
- [alert](#alert)
- [critical](#critical)
- [error](#error)
- [warning](#warning)
- [notice](#notice)
- [info](#info)
- [debug](#debug)
- [log](#log)

<hr />

### getCurrentChannel

**Description:**

Return name of current channel.

**Parameters:**

- (None)

**Returns:**

- (string)

<hr />

### getDefaultChannel

**Description:**

Return name of default channel.

**Parameters:**

- (None)

**Returns:**

- (string)

<hr />

### addChannel

**Description:**

Add a logger instance as a new channel with the same name.

If an existing instance exists with the same name, it will be overwritten.

**Parameters:**

- `$logger` (object): `Monolog\Logger` object

**Returns:**

- (self)

**Example:**

```
use Monolog\Logger;
use Monolog\Handler\FirePHPHandler;

$my_logger = new Logger('my_logger');
$my_logger->pushHandler(new FirePHPHandler());

$logger->addChannel($my_logger);
```

<hr />

### getChannel

**Description:**

Returns logger instance for a given channel.

**Parameters:**

- `$channel = NULL` (string|null): Name of channel to return. If NULL, the current channel will be returned

**Returns:**

- (object): `Monolog\Logger` object

**Throws:**

- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
try {

    $app_logger = $logger->getChannel('App');

} catch (ChannelNotFoundException $e) {
    die($e->getMessage());
}
```

<hr />

### isChannel

**Description:**

Checks if a given channel name exists.

**Parameters:**

- `$channel` (string)

**Returns:**

- (bool)

**Example:**

```
if ($logger->isChannel('App')) {
    // Do something
}
```

<hr />

### channel

**Description:**

Set the channel name to be used for the next logged event.

By default, all logged events will be logged to the channel marked as "default" in the configuration array.

**Parameters:**

- `$channel` (string)

**Returns:**

- (self)

**Throws:**

- `Bayfront\MonologFactory\Exceptions\ChannelNotFoundException`

**Example:**

```
try {
    
    $logger->channel('Dev')->info('This is an informational log message.');
    
} catch (ChannelNotFoundException $e) {
    die($e->getMessage());
}
```

<hr />

### emergency

**Description:**

System is unusable.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### alert

**Description:**

Action must be taken immediately.

Example: Entire website down, database unavailable, etc.
This should trigger the SMS alerts and wake you up.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### critical

**Description:**

Critical conditions.

Example: Application component unavailable, unexpected exception.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### error

**Description:**

Runtime errors that do not require immediate action but should typically be logged and monitored.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### warning

**Description:**

Exceptional occurrences that are not errors.

Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### notice

**Description:**

Normal but significant events.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### info

**Description:**

Interesting events.

Example: User logs in, SQL logs.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### debug

**Description:**

Detailed debug information.

**Parameters:**

- `$message` (string)
- `$context` (array)

**Returns:**

- (void)

<hr />

### log

**Description:**

Logs with an arbitrary level.

**Parameters:**

- `$level` (mixed)
- `$message` (string)
- `$context` (array)

**Returns:**

- (void)