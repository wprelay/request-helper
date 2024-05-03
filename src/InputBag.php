<?php

namespace Cartrabbit\Request;

class InputBag extends ParameterBag
{

    public function get($key, $default = null)
    {

        $value = parent::get($key, $this);

        return $this === $value ? $default : $value;
    }

    /**
     * Replaces the current input values by a new set.
     */
    public function replace(array $inputs = [])
    {
        $this->parameters = [];
        $this->add($inputs);
    }

    /**
     * Adds input values.
     */
    public function add($inputs = [])
    {
        foreach ($inputs as $input => $value) {
            $this->set($input, $value);
        }
    }

    public function set($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Returns the parameter value converted to string.
     */
    public function getString($key,  $default = '')
    {
        // Shortcuts the parent method because the validation on scalar is already done in get().
        return (string)$this->get($key, $default);
    }
}
