# LCache
Foundation Library for Coherent, Multi-Layer Caching

## Testing (on Fedora)

 1. Install packages:

    ```
    sudo dnf install -y php-cli composer php-phpunit-PHPUnit php-phpunit-DbUnit php-pecl-apcu
    ```

 2. Enable APCu caching for the CLI:

    ```
    echo "apc.enable_cli=1" | sudo tee -a /etc/php.d/40-apcu.ini
    ```

 3. From the project root directory:

    ```
    composer install
    composer test
    ```
