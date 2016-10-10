# LCache
Foundation Library for Coherent, Multi-Layer Caching

[![Build Status](https://travis-ci.org/lcache/lcache.svg?branch=master)](https://travis-ci.org/lcache/lcache)
[![Coverage Status](https://coveralls.io/repos/github/lcache/lcache/badge.svg?branch=master)](https://coveralls.io/github/lcache/lcache?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lcache/lcache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lcache/lcache/?branch=master)
[![Documentation](https://img.shields.io/badge/docs-latest-brightgreen.svg?style=flat)](https://lcache.github.io/lcache/)
[![API](https://img.shields.io/badge/api-latest-brightgreen.svg?style=flat)](https://lcache.github.io/lcache/api/master)
[![License](https://poser.pugx.org/lcache/lcache/license)](https://packagist.org/packages/lcache/lcache)

## Testing

 1. Install packages:
 
    **On Fedora:**

    ```
    sudo dnf install -y php-cli composer php-phpunit-PHPUnit php-phpunit-DbUnit php-pecl-apcu php-pecl-xdebug php-opcache
    ```
    
    **On macOS:**
    
    ```
    brew install php70-xdebug 
    brew install php70-opcache
    brew install php70-apcu
    ```

 2. Enable APCu caching for the CLI, if necessary:

    ```
    echo "apc.enable_cli=1" | sudo tee -a /etc/php.d/40-apcu.ini
    ```
    
    Replace last argument with path to your php.ini. (`php -i | grep php.ini`).
    This setting may already be enabled on your system; to find out, run:
    ```
    php -i | grep -i enable_cli
    ```
    You will see `apc.enable_cli => On => On` if it is enabled.

 3. From the project root directory:

    ```
    composer install
    composer test
    ```
    
## Support

LCache is maintained and sponsored by [Pantheon](https://pantheon.io/).
