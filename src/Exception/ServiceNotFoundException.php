<?php

declare(strict_types=1);

namespace Slon\Container\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends Exception implements
    NotFoundExceptionInterface {}
