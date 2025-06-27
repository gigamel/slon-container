<?php

declare(strict_types=1);

namespace Slon\Container\Exception;

use RuntimeException;

final class CircularReferenceException extends RuntimeException {}
