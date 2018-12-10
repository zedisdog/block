<?php
/**
 * Created by zed.
 */
declare(strict_types=1);
namespace Zed\Block;


use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zed\Block\Contracts\ContainerInterface;
use Zed\Block\Contracts\ServiceProvider;
use Zed\Block\Exceptions\NotFoundException;
use Zed\Block\Exceptions\RuntimeException;

class Container implements ContainerInterface, \ArrayAccess
{
    /**
     * @var array
     */
    protected $keys;
    /**
     * @var array
     */
    protected $items;
    /**
     * @var array
     */
    protected $singles;

    public function set(string $id, $item, bool $single = false): void
    {
        $this->items[$id] = [
            'single' => $single,
            'value' => $item
        ];
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @throws \ReflectionException
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        // id can only be string
        if (!is_string($id)) {
            throw new RuntimeException('dependency id can only be string.');
        }
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('dependency [%s] can not be found.', $id));
        }
        // if requested item is a singleton, just return
        if (isset($this->singles[$id])) {
            return $this->singles[$id];
        }

        $item = $this->items[$id];

        if (!is_object($item['value']) || $item['value'] instanceof \Closure) {
            $object = $this->build($item);
        } else {
            $object = $item['value'];
        }

        if ($item['single']) {
            $this->singles[$id] = $object;
        }

        return $object;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->singles[$id]) || isset($this->items[$id]);
    }

    /**
     * @param string|ServiceProvider $provider
     */
    public function register($provider): void
    {
        if (is_string($provider) && class_exists($provider)) {
            $provider = new $provider;
        }

        if (!($provider instanceof ServiceProvider)) {
            throw new RuntimeException('invalid service provider');
        }

        $provider->register($this);
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     * @throws \ReflectionException
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->singles[$offset]);
        unset($this->items[$offset]);
    }

    /**
     * @param $item
     * @return mixed|string
     * @throws \ReflectionException
     */
    protected function build($item)
    {
        if (is_string($item['value']) && class_exists($item['value'])) {
            $object = $this->instantiation($item['value']);
        } elseif ($item['value'] instanceof \Closure) {
            $object = $this->invoke($item['value']);
        } else {
            $object = $item['value'];
        }

        return $object;
    }

    /**
     * @param \Closure $value
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invoke(\Closure $value)
    {
        $reflection = new \ReflectionFunction($value);
        $params = $reflection->getParameters();
        if (!empty($params)) {// parse params
            $args = $this->resolveParams($params);
            return $reflection->invokeArgs($args);
        } else {
            return $reflection->invoke();
        }
    }

    /**
     * @param string $value
     * @return mixed
     * @throws \ReflectionException
     */
    protected function instantiation(string $value)
    {
        $reflection = new \ReflectionClass($value);
        $params = $reflection->getConstructor()->getParameters();
        if (!empty($params)) {// parse params
            $args = $this->resolveParams($params);
            return new $value(...$args);
        } else {
            return new $value;
        }
    }

    /**
     * @param array $params
     * @return array
     * @throws \ReflectionException
     */
    protected function resolveParams(array $params): array
    {
        $args = [];
        foreach ($params AS $arg) {
            if ($class = $arg->getClass()) {
                if ($this->has($class->name)) {
                    array_push($args, $this->get($class->name));
                } elseif ($arg->allowsNull()) {
                    array_push($args, null);
                } else {
                    throw new RuntimeException(sprintf('unknown param [%s]', $arg->name));
                }
            }
        }
        return $args;
    }
}