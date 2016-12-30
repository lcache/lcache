<?php

/**
 * @file
 * This is a factory class to be used for creation and instantiation of L2
 * driver instances.
 */

namespace LCache;

/**
 * L2CacheFactory creation.
 *
 * @author ndobromirov
 */
class L2CacheFactory
{
    private $options;

    /**
     * Factory class constructor.
     *
     * @see L2CacheFactory::setDriverOptions()
     *
     * @param array $driverOptions
     *   Options keyed by driver name.
     */
    public function __construct(array $driverOptions = [])
    {
        $this->setDriverOptions($driverOptions);
    }

    /**
     * Factory driver options mutator.
     *
     * Allows the configuration of driver options after factory instantiation.
     *
     * @param array $driverOptions
     *   Options keyed by driver name.
     *   Example: ['driver_1' => ['option_1' => 'value_1', ...], ...]
     */
    public function setDriverOptions($driverOptions)
    {
        $this->options = $driverOptions;
    }

    /**
     * Factory driver options accessor.
     *
     * @see L2CacheFactory::setDriverOptions()
     *
     * @return array
     *   The aggregated configurations data for all drivers.
     */
    public function getDriverOptions()
    {
        return $this->options;
    }

    /**
     * Factory method for the L2 hierarchy.
     *
     * @param string $name
     *   String driver name to instantiate.
     * @param array $options
     *   Driver options to overwrite the ones already present for the driver
     *   within the factory class.
     * @return L2
     *   Concrete descendant of the L2 abstract.
     */
    public function create($name, array $options = [])
    {
        $factoryName = 'create' . $name;
        if (!method_exists($this, $factoryName)) {
            $factoryName = 'createStatic';
        }

        $defaults = isset($this->options[$name]) ? $this->options[$name] : [];
        $driverOptions = array_merge($defaults, $options);

        $l1CacheInstance = call_user_func([$this, $factoryName], $driverOptions);
        return $l1CacheInstance;
    }

    protected function createStatic($options)
    {
        return new StaticL2();
    }

    /**
     * Possible options:
     *  - handle - the PDO handle instance.
     *  - prefix - tables prefix to use (default - '').
     *  - log - whether to log errors locally in the instance (default - false).
     *
     * @param array $options
     * @return \LCache\DatabaseL2
     */
    protected function createDatabase($options)
    {
        // Apply defaults.
        $options += ['prefix' => '', 'log' => false];
        return new DatabaseL2(
            $options['handle'],
            $options['prefix'],
            $options['log']
        );
    }
}
