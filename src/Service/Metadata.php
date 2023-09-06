<?php declare(strict_types=1);

namespace Space48\SSO\Service;

use Space48\SSO\Exception\ServiceException;
use Space48\SSO\Model\AuthInstance;
use Space48\SSO\Model\Config;

class Metadata
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var AuthInstance
     */
    private $authInstance;

    public function __construct(
        Config $config,
        AuthInstance $authInstance
    ) {
        $this->config = $config;
        $this->authInstance = $authInstance;
    }

    public function isAvailable(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * @throws ServiceException
     */
    public function getMetadata(): string
    {
        $settings = $this->authInstance->get()->getSettings();

        try {
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);
        } catch (\Exception $e) {
            throw new ServiceException(__('Unable to validate SSO service metadata: %error', ['error' => $e->getMessage()]), $e);
        }

        if (!empty($errors)) {
            throw new ServiceException(__('Invalid SSO service metadata: %error', ['error' => implode(', ', $errors)]));
        }

        return $metadata;
    }
}
