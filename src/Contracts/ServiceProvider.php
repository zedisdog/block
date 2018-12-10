<?php
/**
 * Created by zed.
 */
declare(strict_types=1);
namespace Zed\Block\Contracts;

interface ServiceProvider
{
    public function register(ContainerInterface $container): void;
}