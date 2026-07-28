<?php

namespace Byl\Laravel\Exceptions;

use RuntimeException;

/**
 * Byl-тэй холбогдох боломжгүй байсан (timeout, DNS, сүлжээний алдаа).
 */
class ConnectionException extends RuntimeException implements BylException {}
