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
        for ($i=1, $iMax = count($args); $i < $iMax; $i++) {
            $argument = $args[$i];
            if (($argument[0] ?? '') === '-') {
                $this->options[$argument[1] ?? ''] = substr($argument, 2);
            } else {
                $this->parameters[] = $argument;
            }
        }
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
     * @return string|null
     */
    public function getOption(string $optionName): ?string
    {
        return $this->options[$optionName] ?? null;
    }
}
