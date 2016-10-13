# Contributing to LCache

Thank you for your interest in contributing to the LCache project.  LCache is used in multiple frameworks, including Drupal 8, Drupal 7 and WordPress; it is therefore very important that all changes made to the common library are well tested, and generic enough to work in multiple environments.

Here are some of the guidelines you should follow to make the most of your efforts:

## Code Style Guidelines

Consolidation adheres to the [PSR-2 Coding Style Guide](http://www.php-fig.org/psr/psr-2/) for PHP code.

## Pull Request Guidelines

Every pull request is run through:

  - phpcs -n --standard=PSR2 src
  - phpunit
  - [Scrutinizer](https://scrutinizer-ci.com/g/lcache/lcache/)
  
It is easy to run the unit tests and code sniffer locally; just run:

  - composer cs

To run the code beautifier, which will fix many of the problems reported by phpcs:

  - composer cbf

These two commands (`composer cs` and `composer cbf`) are defined in the `scripts` section of [composer.json](composer.json).

After submitting a pull request, please examine the Scrutinizer report. It is not required to fix all Scrutinizer issues; you may ignore recommendations that you disagree with. The spacing patches produced by Scrutinizer do not conform to PSR2 standards, and therefore should never be applied. DocBlock patches may be applied at your discression. Things that Scrutinizer identifies as a bug nearly always need to be addressed.

Pull requests must pass phpcs and phpunit in order to be merged; ideally, new functionality will also maintain full test coverage.

## Local Testing

To run the phpunit tests prior to submitting a pull request, follow the instructions below.

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

## Documentation

The LCache API documentation is built from PHP docblock comments using [Sami](https://github.com/FriendsOfPHP/Sami), the Symfony API documentation generator.  Documentation updates are built automatically on passing build on the master branch. If you would like to build the API documentation locally, run:

 - composer sami-install
 - composer api

The [documentation site](https://lcache.github.io/lcache/) is currently a simple static html site built using [GitHub Pages](https://pages.github.com/). To contribute to documentation, submit a PR on the [gh-pages](https://github.com/lcache/lcache/tree/gh-pages) branch.

