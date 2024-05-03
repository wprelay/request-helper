<?php


namespace Cartrabbit\Request;
class ParameterBag
{
    /**
     * Parameter storage.
     */
    protected $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }


    public function all(string $key = null): array
    {
        if (null === $key) {
            return $this->parameters;
        }
        return $this->parameters[$key] ?? [];
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function replace(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function add(array $parameters = [])
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    public function get(string $key, $default = null)
    {
        return \array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }

    public function set(string $key, mixed $value)
    {
        $this->parameters[$key] = $value;
    }

    public function has(string $key)
    {
        return \array_key_exists($key, $this->parameters);
    }

    public function remove(string $key)
    {
        unset($this->parameters[$key]);
    }

    /**
     * Returns the number of parameters.
     */
    public function count()
    {
        return \count($this->parameters);
    }
}
