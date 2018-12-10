<?php
/**
 * Created by zed.
 */
declare(strict_types=1);
namespace Zed\Block\Contracts;

use Psr\Container\ContainerInterface as BaseContainerInterface;

interface ContainerInterface extends BaseContainerInterface
{

    /**
     * @param string $id
     * @param $item
     */
    public function set(string $id, $item): void;

    /**
     * @param ServiceProvider|string $provider
     */
    public function register($provider): void;
}