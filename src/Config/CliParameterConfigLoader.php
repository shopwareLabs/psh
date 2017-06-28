<?php
namespace Shopware\Psh\Config;

class CliParameterConfigLoader implements ConfigLoader
{
    /** @var array */
    private $inputArguments = [];

    /** @var ConfigBuilder */
    private $configBuilder;

    public function __construct(ConfigBuilder $configBuilder, $inputArguments = []) {
        $this->inputArguments = $inputArguments;
        $this->configBuilder = $configBuilder;
    }

    public function isSupported(): bool
    {
        return true;
    }

    public function load(): Config
    {
        $constants = [];
        foreach($this->inputArguments as $argument) {
            $argument = explode('=', $argument);
            if(count($argument) === 2) {
                $constants[$argument[0]] = $argument[1];
            }
        }
        $this->configBuilder->setConstants($constants);

        return $this->configBuilder->create();
    }
}
