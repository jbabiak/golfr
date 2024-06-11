<?php

namespace Drupal\eca_queue\Exception;

use Drupal\Core\Queue\RequeueException;

/**
 * Thrown when an enqueued task is not yet due for processing.
 */
class NotYetDueForProcessingException extends RequeueException {}
