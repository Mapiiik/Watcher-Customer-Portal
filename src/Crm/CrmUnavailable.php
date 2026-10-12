<?php
declare(strict_types=1);

namespace App\Crm;

use Cake\Http\Exception\HttpException;
use Throwable;

/**
 * The CRM did not answer, or answered with something the portal cannot use. The customer sees
 * that the portal is not available for now, the log says why.
 */
final class CrmUnavailable extends HttpException
{
    /**
     * @param string $message What went wrong, for the log.
     * @param \Throwable|null $previous What caused it.
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 503, $previous);
    }
}
