<?php


namespace App\Console;


class ArgumentHolder
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $options = [];

    public function __construct()
    {
        $args = $_SERVER['argv'];
        for ($i = 1, $iMax = count($args); $i < $iMax; $i++) {
            $argument = $args[$i];
            if (($argument[0] ?? '') === '-') {
                $optionKey = $argument[1] ?? '';
                $optionValue = substr($argument, 2);
                if (strpos($optionValue, '"') === 0) {
                    $optionValue = $this->getFullLexeme($optionValue, $i);
                }
                $this->addOption($optionKey, $optionValue);
            } else {
                $this->parameters[] = $argument;
            }
        }
    }

    protected function getFullLexeme(string $beginValue, int &$argsIndex): string
    {
        $value = $beginValue;
        $args = $_SERVER['argv'];
        $continueFind = true;

        while ($continueFind && isset($args[++$argsIndex])) {
            $value .= $args[$argsIndex];
            $continueFind = !(substr($value, -3) === '\\\\"' || (substr($value, -1) === '"' && substr($value, -2) !== '\\"'));
        }

        return $value;
    }

    /**
     * @param int $paramName
     *
     * @return string|null
     */
    public function getParameter(int $paramName): ?string
    {
        return $this->parameters[$paramName] ?? null;
    }

    /**
     * @param string $optionName
     *
     * @return string|array|null
     */
    public function getOption(string $optionName)
    {
        return $this->options[$optionName] ?? null;
    }

    protected function addOption(string $key, string $value): void
    {
        if (array_key_exists($key, $this->options)) {
            if (is_array($this->options[$key])) {
                $this->options[$key][] = $value;
            } else {
                $this->options[$key] = [$this->options[$key], $value];
            }
        } else {
            $this->options[$key] = $value;
        }
    }
}
