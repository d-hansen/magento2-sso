<?php declare(strict_types=1);

namespace Space48\SSO\Service;

use Magento\Framework\App\Response\RedirectInterface;
use Space48\SSO\Exception\ServiceException;
use Space48\SSO\Exception\UserException;
use Space48\SSO\Model\AuthInstance;
use Space48\SSO\Model\Config;
use Space48\SSO\Model\Storage;
use Space48\SSO\Model\Url;
use Space48\SSO\Model\UserManager;

class Login
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var AuthInstance
     */
    private $authInstance;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(
        Config $config,
        AuthInstance $authInstance,
        Storage $storage,
        Url $url,
        UserManager $userManager
    ) {

        $this->config = $config;
        $this->authInstance = $authInstance;
        $this->storage = $storage;
        $this->url = $url;
        $this->userManager = $userManager;
    }

    public function isAvailable(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * @return string Identity provider login redirect URL.
     *
     * @throws ServiceException
     */
    public function initLogin(): string
    {
        $auth = $this->authInstance->get();

        try {
            $redirectUrl = $auth->login(
                $this->url->getReturnUrl(),
                [],
                false,
                false,
                true
            );
        } catch (\Exception $e) {
            throw new ServiceException(__(
                'Failed to initialise a login request: %error',
                ['error' => $e->getMessage()]
            ), $e);
        }

        $this->storage->setLastRequestId($auth->getLastRequestID());

        return $redirectUrl;
    }

    /**
     * @return string Post login redirect URL.
     *
     * @throws ServiceException
     * @throws UserException
     */
    public function processLoginResponse(): string
    {
        $auth = $this->authInstance->get();
        $requestId = $this->storage->getLastRequestId(true);

        try {
            $auth->processResponse($requestId);

            if ($auth->getLastErrorException()) {
                throw $auth->getLastErrorException();
            }
        } catch (\OneLogin\Saml2\ValidationError $e) {
            if ($e->getCode() === \OneLogin\Saml2\ValidationError::ASSERTION_EXPIRED) {
                throw new UserException(__('Provided single sign-on response is expired.'), $e);
            }
            throw new ServiceException(__(
                'Failed to validate SSO login response: %error',
                ['error' => $e->getMessage()]
            ), $e);
        } catch (\Exception $e) {
            throw new ServiceException(__(
                'Failed to process SSO login response: %error',
                ['error' => $e->getMessage()]
            ), $e);
        }

        if (!$auth->isAuthenticated()) {
            throw new UserException(__('Single sign-on authentication failed.'));
        }

        $userFirstName = $this->getRequiredAttribute($auth, $this->config->getFirstNameAttributeName());
        $userLastName = $this->getRequiredAttribute($auth, $this->config->getLastNameAttributeName());
        $userName = $userFirstName . '.' . $userLastName;
        $userEmail = $this->getRequiredAttribute($auth, $this->config->getEmailAttributeName());
        $user = $this->userManager->upsertUser($userName, $userEmail, $userFirstName, $userLastName,
            $this->getMagentoRoleName($auth)
        );

        $this->userManager->login($user);

        return $auth->redirectTo('', [], true);
    }

    private function getMagentoRoleName(\OneLogin\Saml2\Auth $auth): string
    {
        if ($this->config->hasStaticMagentoRoleName()) {
            return $this->config->getStaticMagentoRoleName();
        }

        return $this->getRequiredAttribute($auth, $this->config->geRoleAttributeName());
    }

    /**
     * @throws UserException
     */
    private function getRequiredAttribute(\OneLogin\Saml2\Auth $auth, string $attributeName): string
    {
        $value = $auth->getAttribute($attributeName);

        if (!is_array($value)) {
            throw new UserException(__(
                'Required attribute "%attr" was not provided by the Identity Provider.',
                ['attr' => $attributeName]
            ));
        }

        return (string)current($value);
    }
}
