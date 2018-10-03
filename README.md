# Basic Fat-free framework app skeleton

It includes:

- app structure
- cli.php for console commands
- set of usefull helpers - App/helpers.php
- better DB - https://github.com/webvimark/pixie
- assets combine - see combine() function
- error handler - App/Handlers/ErrorHandler.php and error view - views/error.html
- layout example with custom header
- ExampleController and example views - views/example/

## Installation

Create "configs/env.ini" or copy from "_env.local.example.ini" or "_env.production.example.ini"

```
chmod 777 tmp
chmod 777 www/combine
composer install
composer dump -o
```
## How to learn

There are not so many files, so just go through them =)

## Tips & tricks

- change layout for specific actions by 
    ```php
    $fw->set('APP.LAYOUT', 'path/to/layout.html')
    ```
    or disable it by
    ```php
    $fw->clear('APP.LAYOUT')
    ```
- use "v." prefix for system template variables. Like "v.title" or "v.meta"
- logs will be located in "tmp/logs" by default

## Usefull packages to extend you app

- pagination - https://github.com/jasongrimes/php-paginator
- form & request validator - https://github.com/rakit/validation
- debug panel - https://github.com/maximebf/php-debugbar
- database seeder - https://github.com/tebazil/db-seeder