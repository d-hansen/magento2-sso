<?php declare(strict_types=1);

namespace Space48\SSO\Controller\Adminhtml\Login;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Space48\SSO\Exception\ServiceException;
use Space48\SSO\Exception\UserException;
use Space48\SSO\Service\Login;
use Psr\Log\LoggerInterface;

class Process implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var Login
     */
    private $loginService;

    /**
     * @var ForwardFactory
     */
    private $forwardFactory;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Login $loginService,
        ForwardFactory $forwardFactory,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {

        $this->loginService = $loginService;
        $this->forwardFactory = $forwardFactory;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!$this->loginService->isAvailable()) {
            return $this->forwardFactory
                ->create()
                ->forward('noroute');
        }

        try {
            $redirectUrl = $this->loginService->processLoginResponse();
        } catch (ServiceException $e) {
            $this->messageManager->addErrorMessage('SSO Service Exception: ' . $e->getMessage());
            $this->messageManager->addWarningMessage('Please have your administrator check your SSO IdP Configuration!');
            $this->logger->error('SSO Service Exception: ' . $e->getMessage());
            $this->logger->debug('SSO Additional Info: ' . $e->getAdditionalInfo());
            return $this->forwardFactory
                ->create()
                ->forward('adminhtml/auth/login');
        } catch (UserException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->forwardFactory
                ->create()
                ->forward('adminhtml/auth/login');
        }

        return $this->redirectFactory
            ->create()
            ->setUrl($redirectUrl);
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
