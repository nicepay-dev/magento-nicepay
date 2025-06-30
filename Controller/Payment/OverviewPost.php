<?php

namespace Nicepay\NicePayment\Controller\Payment;

use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Checkout\Exception;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\PaymentException;
use Magento\Multishipping\Controller\Checkout;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;
use Magento\Store\Model\StoreManagerInterface;
use Nicepay\NicePayment\Helper\Data as NicepayHelper;
use Nicepay\NicePayment\Logger\Logger as NicepayLogger;


class OverviewPost extends Checkout
{

    protected $formKeyValidator;


    protected $agreementsValidator;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var NicepayHelper
     */
    protected $nicepayHelper;

    /**
     * @var CheckoutHelper
     */
    protected $checkoutHelper;

    /**
     * @var NicepayLogger
     */
    protected $nicepayLogger;

    /**
     * OverviewPost constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param Validator $formKeyValidator
     * @param AgreementsValidatorInterface $agreementValidator
     * @param StoreManagerInterface $storeManager
     * @param nicepayHelper $nicepayHelper
     * @param CheckoutHelper $checkoutHelper
     * @param NicepayLogger $nicepayLogger
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        Validator $formKeyValidator,
        AgreementsValidatorInterface $agreementValidator,
        StoreManagerInterface $storeManager,
        NicepayHelper $nicepayHelper,
        CheckoutHelper $checkoutHelper,
        NicepayLogger $nicepayLogger
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->agreementsValidator = $agreementValidator;
        $this->storeManager = $storeManager;
        $this->nicepayHelper = $nicepayHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->nicepayLogger = $nicepayLogger;

        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement
        );
    }

    /**
     * Overview action
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->_forward('backToAddresses');
            return;
        }


        try {


            $payment = $this->getRequest()->getPost('payment');
            $paymentInstance = $this->_getCheckout()->getQuote()->getPayment();


            $this->_getCheckout()->createOrders();
            $this->_getState()->setCompleteStep(State::STEP_OVERVIEW);

            $baseUrl = $this->storeManager->getStore()->getBaseUrl();

            $nicepayPaymentMethod = $this->nicepayHelper->nicepayPaymentMethod($paymentInstance->getMethod());
            $orderIds = $this->_getCheckout()->getOrderIds();
            if ($nicepayPaymentMethod) {
                if (empty($orderIds)) {
                    $this->messageManager->addError(
                        __('Failed to create order.')
                    );
                    $this->_redirect('*/*/billing');
                }
                $redirect = $baseUrl . '/nicepay/checkout/invoice';
                $this->_redirect($redirect);
            } else {
                //OTHERS
                $this->_getState()->setActiveStep(State::STEP_SUCCESS);
                $this->_getCheckout()->getCheckoutSession()->clearQuote();
                $this->_getCheckout()->getCheckoutSession()->setDisplaySuccess(true);
                $this->_redirect('*/*/success');
            }
        } catch (PaymentException $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addError($message);
            }
            $this->_redirect('*/*/billing');
        } catch (Exception $e) {
            $this->checkoutHelper->sendPaymentFailedEmail(
                $this->_getCheckout()->getQuote(),
                $e->getMessage(),
                'multi-shipping'
            );
            $this->_getCheckout()->getCheckoutSession()->clearQuote();
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/cart');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->checkoutHelper->sendPaymentFailedEmail(
                $this->_getCheckout()->getQuote(),
                $e->getMessage(),
                'multi-shipping'
            );
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/*/billing');
        } catch (\Exception $e) {
            $this->nicepayLogger->critical($e);
            try {
                $this->checkoutHelper->sendPaymentFailedEmail(
                    $this->_getCheckout()->getQuote(),
                    $e->getMessage(),
                    'multi-shipping'
                );
            } catch (\Exception $e) {
                $this->nicepayLogger->error($e->getMessage());
            }
            $this->nicepayLogger->info('Log error checkout: ');
            $this->nicepayLogger->info($e->getMessage());
            $this->messageManager->addError(__('Order place error'));
            $this->_redirect('*/*/billing');
        }
    }
}
