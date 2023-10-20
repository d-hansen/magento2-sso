<?php declare(strict_types=1);

namespace Space48\SSO\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ServiceException extends LocalizedException
{
    /**
     * @var response
     */
    private $additional_info;

    /**
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception $cause
     * @param int $code
     * @param string $response
     */

    public function __construct(Phrase $phrase, \Exception $cause = null, $code = 0, $additional_info = null)
    {
        $this->additional_info = $additional_info;
        parent::__construct($phrase, $cause, $code);
    }

    public function getAdditionalInfo()
    {
        return $this->additional_info;
    }
}
