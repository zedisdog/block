<?php
/**
 * Created by zed.
 */

namespace Zed\Block\Exceptions;


use Psr\Container\ContainerExceptionInterface;

class RuntimeException extends \RuntimeException implements ContainerExceptionInterface
{

}