<?php
/**
 * Created by zed.
 */

namespace Zed\Block\Exceptions;


use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{

}