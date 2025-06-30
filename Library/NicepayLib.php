<?php

namespace Nicepay\NicePayment\Library;

use Nicepay\common\NICEPay;
use Nicepay\NicePayment\Helper\CommonHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Nicepay\NicePayment\Helper\DateHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Exception;

use Nicepay\common\NicepayError;
use Nicepay\Data\Model\AccessToken;
use Nicepay\service\snap\{Snap, SnapVAService, SnapQrisService, SnapEwalletService, SnapPayoutService};
use Nicepay\service\v2\{BaseV2Service, V2VAService, V2QrisService, V2EwalletService, V2PayoutService, V2CardService, V2CvsService, V2PayloanService, V2RedirectService};
use Nicepay\Data\Model\{Qris,  VirtualAccount, Ewallet, Payout, Cancel, InquiryStatus, CvS, Redirect, Card, Payloan};
use Nicepay\utils\Helper;
use Nicepay\utils\NicepayCons;
use Nicepay\NicePayment\Logger\Logger as NiceLogger;

class NicepayLib extends AbstractHelper
{
    public $requestData = array();

    public $nicepayConfig;

    public $apiVersion;
    public $commonHelper;


    public $dbProcessUrl;

    public $snapDbProcessUrl;

    private $niceLogger;

    protected $scopeConfig;
    protected $logPrefix;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        NiceLogger $logger,
    ) {
        $this->apiVersion = 'v2'; //default
        $this->commonHelper = new CommonHelper();
        $this->scopeConfig = $scopeConfig;
        $this->niceLogger = $logger;
        $this->logPrefix = uniqid() . ' Nicepay Library - ';
    }

    public function set($name, $value)
    {
        $this->requestData[$name] = $value;
    }

    public function get($name)
    {
        if (isset($this->requestData[$name])) {
            return $this->requestData[$name];
        }
        return "";
    }

    public function getMerchantKey($payMethod)
    {
        $paymentCode = CommonHelper::getNicepayPaymentCode($payMethod);
        return $this->getPaymentConfig($paymentCode, 'merchant_key');
    }

    public function getIMidConfig($payMethod)
    {
        $paymentCode = CommonHelper::getNicepayPaymentCode($payMethod);
        return $this->getPaymentConfig($paymentCode, 'merchant_id');
    }

    public function setNicepayConfig($payMethod)
    {
        $this->niceLogger->info($this->logPrefix . 'setNicepayConfig for pay method ' . $payMethod);
        $snapPaymethod = ["02", "05", "07", "08"];

        $paymentCode = CommonHelper::getNicepayPaymentCode($payMethod);

        $isProduction = $this->getPaymentConfig($paymentCode, 'nicepay_env') == 'prod' ? true : false;
        $iMid = $this->getPaymentConfig($paymentCode, 'merchant_id');

        $this->niceLogger->info($this->logPrefix . 'isProduction : ' . $isProduction);
        $this->niceLogger->info($this->logPrefix . 'iMid :' . $iMid);

        // Check if using snap
        if (in_array($payMethod, $snapPaymethod)) {
            $this->apiVersion = $this->getPaymentConfig($paymentCode, 'nicepay_api_version');
            $this->niceLogger->info($this->logPrefix . 'apiVersion ' . $this->apiVersion);
        }

        if ($this->apiVersion == 'snap') {

            $this->niceLogger->info($this->logPrefix . 'Using Snap API ');

            $privateKey = $this->getPaymentConfig($paymentCode, 'private_key');
            $clientSecret = $this->getPaymentConfig($paymentCode, 'client_secret');
            $externalId = $iMid . $payMethod  . DateHelper::getFormattedTimestampV2();
            $timeStamp = DateHelper::getFormattedDate();


            $this->nicepayConfig = NICEPay::builder()
                ->setIsProduction($isProduction)
                ->setPrivateKey($privateKey)
                ->setClientSecret($clientSecret)
                ->setPartnerId($iMid)
                ->setExternalID($externalId)
                ->setTimestamp($timeStamp)
                ->build();
        } else {

            $this->niceLogger->info($this->logPrefix . 'Using V2 API ');

            $merchantKey = $this->getPaymentConfig($paymentCode, 'merchant_key');
            $this->nicepayConfig = NICEPay::builder()
                ->setIsProduction($isProduction)
                ->setClientSecret($merchantKey)
                ->setPartnerId($iMid)
                ->build();
        }

        $this->niceLogger->info($this->logPrefix . 'nicepayConfig Completed');
    }

    public function nicepayRegistration()
    {

        $payMethod = $this->get('payMethod');
        $this->niceLogger->info($this->logPrefix . 'nicepayRegistration start - payMethod ' . $payMethod);

        // SET NICEPAY CONFIG 
        $this->setNicepayConfig($payMethod);

        $isSnap = $this->apiVersion == 'snap' ? true : false;

        $this->niceLogger->info($this->logPrefix . 'REQUEST DATA ', ['requestData' => $this->requestData]);
        if ($isSnap && $payMethod == '02') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist Snap Virtual Account ');
            return $this->registSnapVirtualAccount();
        } else if ($isSnap && $payMethod == '05') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist Snap Ewallet ');
            return $this->registSnapEwallet();
        } else if ($isSnap && $payMethod == '07') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist Snap Payout ');
            return $this->registSnapPayout();
        } else if ($isSnap && $payMethod == '08') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist Snap Qris ');
            return $this->registSnapQris();
        } else if ($payMethod == '00') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Redirect ');
            return $this->registV2Redirect();
        } else if ($payMethod == '01') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Card ');
            return $this->registV2Card();
        } else if ($payMethod == '02') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Virtual Account ');
            return $this->registV2VirtualAccount();
        } else if ($payMethod == '03') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Convenience Store');
            return $this->registV2Cvs();
        } else if ($payMethod == '05') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Ewallet ');
            return $this->registV2Ewallet();
        } else if ($payMethod == '06') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Payloan ');
            return $this->registV2Payloan();
        } else if ($payMethod == '07') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Payout ');
            return $this->registV2Payout();
        } else if ($payMethod == '08') {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Qris ');
            return $this->registV2VQris();
        }

        return null;
    }

    public function nicepayInquiryStatus()
    {


        $payMethod = $this->get('payMethod');
        $this->niceLogger->info($this->logPrefix . 'nicepayInquiryStatus start - payMethod ' . $payMethod);

        // SET NICEPAY CONFIG 
        $this->setNicepayConfig($payMethod);

        $isSnap = $this->apiVersion == 'snap' ? true : false;

        $this->niceLogger->info($this->logPrefix . 'Inquiry Status- REQUEST DATA ', ['requestData' => $this->requestData]);

        if ($isSnap && $payMethod == '02') {
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Virtual Account ');
            return $this->statusInquirySnapVirtualAccount();
        } else if ($isSnap && $payMethod == '05') {
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Ewallet ');
            return $this->statusInquirySnapEwallet();
        } else if ($isSnap && $payMethod == '07') {
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Payout ');
            return $this->statusInquirySnapPayout();
        } else if ($isSnap && $payMethod == '08') {
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Qris ');
            return $this->statusInquirySnapQris();
        } else if ($payMethod == '07') {
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status V2 Payout ');
            return $this->statusInquiryV2Payout();
        } else {
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status V2 ');
            return $this->statusInquiryV2();
        }
    }


    public function nicepayPayout()
    {

        $payMethod = $this->get('payMethod');
        $action = $this->get('payoutAction');

        $this->niceLogger->info($this->logPrefix . 'nicepayPayout start - payMethod ' . $payMethod);

        // SET NICEPAY CONFIG 
        $this->setNicepayConfig($payMethod);

        $isSnap = $this->apiVersion == 'snap' ? true : false;

        $this->niceLogger->info($this->logPrefix . 'REQUEST DATA ', ['requestData' => $this->requestData]);
        if ($isSnap && $action == 'APPROVE') {
            $this->niceLogger->info($this->logPrefix . 'Call Approve Snap Payout ');
            return $this->approveSnapPayout();
        } else if ($isSnap && $action == 'REJECT') {
            $this->niceLogger->info($this->logPrefix . 'Call Reject Snap Payout ');
            return $this->rejectSnapPayout();
        } else if ($isSnap && $action == 'STATUS_INQUIRY') {
            $this->niceLogger->info($this->logPrefix . 'Call Status Inquiry Snap Payout ');
            return $this->statusInquirySnapPayout();
        } else if ($isSnap && $action == 'BALANCE_INQUIRY') {
            $this->niceLogger->info($this->logPrefix . 'Call Balance Inquiry Snap Payout ');
            return $this->balanceInquirySnapPayout();
        } else if ($isSnap && $action == 'CANCEL') {
            $this->niceLogger->info($this->logPrefix . 'Call Cancel Snap Payout ');
            return $this->cancelSnapPayout();
        } else if ($action == 'APPROVE') {
            $this->niceLogger->info($this->logPrefix . 'Call Approve V2 Payout ');
            return $this->approveV2Payout();
        } else if ($action == 'REJECT') {
            $this->niceLogger->info($this->logPrefix . 'Call Reject V2 Payout ');
            return $this->rejectV2Payout();
        } else if ($action == 'STATUS_INQUIRY') {
            $this->niceLogger->info($this->logPrefix . 'Call Status Inquiry V2 Payout ');
            return $this->statusInquiryV2Payout();
        } else if ($action == 'BALANCE_INQUIRY') {
            $this->niceLogger->info($this->logPrefix . 'Call Balance Inquiry V2 Payout ');
            return $this->balanceInquiryV2Payout();
        } else if ($action == 'CANCEL') {
            $this->niceLogger->info($this->logPrefix . 'Call Cancel V2 Payout ');
            return $this->cancelV2Payout();
        }

        $this->niceLogger->info($this->logPrefix . 'nicepayPayout end ');

        return $this->nicepayConfig;
    }

    private function approveSnapPayout()
    {

        $this->niceLogger->info($this->logPrefix . 'approveSnapPayout start ');
        $config = $this->nicepayConfig;

        $requestBody = Payout::builder()
            ->merchantId($config->getPartnerId())
            ->originalReferenceNo($this->get('tXid'))
            ->originalPartnerReferenceNo($this->get('originalPartnerReferenceNo'))
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $payoutService = new SnapPayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Approve Snap Payout with request body ', ['requestBody' => $requestBody->toArray()]);
            $response = $payoutService->approve($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Approve Snap Payout Response ', ['response' => $response->getResponseMessage(), 'responseCode' => $response->getResponseCode()]);
            $this->niceLogger->info("approveSnapPayout end ");
            return $this->handleResponseSnap($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Approve Snap fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function statusInquirySnapPayout(): array
    {

        $this->niceLogger->info($this->logPrefix . 'statusInquirySnapPayout ');
        $config = $this->nicepayConfig;

        $requestBody = InquiryStatus::builder()
            ->setMerchantId($config->getPartnerId())
            ->setOriginalReferenceNo($this->get('tXid'))
            ->setOriginalPartnerReferenceNo(originalPartnerReferenceNo: $this->get('originalPartnerReferenceNo'))
            ->SetBeneficiaryAccountNo($this->get('accountNo'))
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $payoutService = new SnapPayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Payout with request : ', ['requestBody' => $requestBody->toArray()]);
            $response = $payoutService->inquiryStatus($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Payout Response ', ['response' => $response->getResponseMessage()]);
            $this->niceLogger->info($this->logPrefix . "statusInquirySnapPayout end");
            return $this->handleResponseInquiryStatus($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Inquiry Status fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function statusInquirySnapVirtualAccount(): array
    {

        $this->niceLogger->info($this->logPrefix . 'statusInquirySnapPayout ');
        $config = $this->nicepayConfig;

        $requestBody = InquiryStatus::builder()
            ->setPartnerServiceId($this->get('partnerServiceId'))
            ->setCustomerNo($this->get('customerNo'))
            ->setVirtualAccountNo(virtualAccountNo: $this->get('vacctNo'))
            ->setInquiryRequestId("inqVA" . Helper::getFormattedDate())
            ->setTrxId($this->get('referenceNo'))
            ->setTxIdVA($this->get('tXid'))
            ->setTotalAmount($this->get('amt'), "IDR")
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $vaService = new SnapVAService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Virtual Account with Request Body ', ['requestBody' => $requestBody->toArray()]);
            $response = $vaService->inquiryStatus($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Virtual Account Response ', ['response' => $response->getResponseMessage()]);
            $this->niceLogger->info('statusInquirySnapVirtualAccount end');
            return $this->handleResponseInquiryStatus($response, '02');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Inquiry Status VA fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }


    private function statusInquirySnapEwallet(): array
    {

        $this->niceLogger->info($this->logPrefix . 'statusInquirySnapEwallet ');
        $config = $this->nicepayConfig;

        $requestBody = InquiryStatus::builder()
            ->setMerchantId($config->getPartnerId())
            ->setSubMerchantId("")
            ->setOriginalPartnerReferenceNo(originalPartnerReferenceNo: $this->get('referenceNo'))
            ->setOriginalReferenceNO(originalReferenceNo: $this->get('tXid'))
            ->setServiceCode(54)
            ->setTransactionDate(Helper::getFormattedDate())
            ->setExternalStoreId($this->get('externalStoreId'))
            ->setAmount($this->get('amt'), "IDR")
            ->setAdditionalInfo([])
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $ewalletService = new SnapEwalletService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Ewallet with Request Body ', ['requestBody' => $requestBody->toArray()]);
            $response = $ewalletService->inquiryStatus($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Ewallet Response ', ['response' => $response->getResponseMessage()]);
            $this->niceLogger->info("statusInquirySnapEwallet end");
            return $this->handleResponseInquiryStatus($response, '05');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Inquiry Status fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function statusInquirySnapQris(): array
    {

        $this->niceLogger->info($this->logPrefix . 'statusInquirySnapQris ');
        $config = $this->nicepayConfig;
        $storeId = $this->getPaymentConfig('qris', 'store_id') ?? '';

        $requestBody = InquiryStatus::builder()
            ->setOriginalReferenceNo($this->get('tXid'))
            ->setOriginalPartnerReferenceNo($this->get('referenceNo'))
            ->setMerchantId($config->getPartnerId())
            ->setExternalStoreId($this->get('externalStoreId'))
            ->setServiceCode("47")
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $qrisService = new SnapQrisService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Qris with Request Body ', ['requestBody' => $requestBody->toArray()]);
            $response = $qrisService->inquiryStatus($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status Snap Qris Response ', ['response' => $response->getResponseMessage()]);
            return $this->handleResponseInquiryStatus($response, '08');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Inquiry Status Qris fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function verifySignature()
    {

        $signatureString = $this->get('signature');
        $dataString = $this->get('stringToSign');
        $paymentCode = CommonHelper::getNicepayPaymentCode($this->get('payMethod'));
        $publicKeyString = $this->getPaymentConfig($paymentCode, 'public_key') ?? '';

        return Helper::verifySHA256RSA($dataString, $publicKeyString, $signatureString);;
    }

    private function statusInquiryV2Payout()
    {

        $this->niceLogger->info($this->logPrefix . 'statusInquiryV2Payout ');
        $config = $this->nicepayConfig;

        $timeStamp = Helper::getFormattedTimestampV2();
        $iMid = $config->getPartnerId();
        $tXid = $this->get('tXid');
        $accountNo = $this->get('accountNo');

        $requestBody = Payout::builder()
            ->iMid($iMid)
            ->timeStamp($timeStamp)
            ->merchantTokenPayoutInquiry($timeStamp, $iMid, $tXid, $accountNo, $config->getClientSecret())
            ->tXid($tXid)
            ->accountNo($accountNo)
            ->build();

        try {
            $payoutService = new V2PayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status V2 Payout with request ', ['requestBody' => $requestBody->toArrayV2()]);
            $response = $payoutService->inquiryTransaction($requestBody);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Status V2 Payout Response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseInquiryStatus($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Inquiry Status V2 fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }



    private function balanceInquirySnapPayout(): array
    {

        $this->niceLogger->info($this->logPrefix . 'balanceInquirySnapPayout ');
        $config = $this->nicepayConfig;

        $requestBody = Payout::builder()
            ->accountNo($config->getPartnerId())
            ->additionalInfo(
                [
                    "msId" => "",
                ]
            )
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $payoutService = new SnapPayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Balance Snap Payout with reference no ', ['requestBody' => $requestBody->getOriginalPartnerReferenceNo()]);
            $response = $payoutService->checkBalance($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Balance Snap Payout Response ', ['response' => $response->getResponseMessage()]);
            return $this->handleResponseSnap($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Inquiry Balance fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }


    private function balanceInquiryV2Payout(): array
    {

        $this->niceLogger->info($this->logPrefix . 'balanceInquirySnapPayout ');
        $config = $this->nicepayConfig;

        $iMid = $config->getPartnerId();
        $timeStamp = Helper::getFormattedTimestampV2();


        $requestBody = Payout::builder()
            ->iMid($iMid)
            ->timeStamp($timeStamp)
            ->merchantTokenBalancePayout($timeStamp, $iMid, $config->getClientSecret())
            ->build();


        try {
            $payoutService = new V2PayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Balance V2 Payout with request :', ['requestBody' => $requestBody->getOriginalPartnerReferenceNo()]);
            $response = $payoutService->balanceInquiry($requestBody);
            $this->niceLogger->info($this->logPrefix . 'Call Inquiry Balance V2 Payout Response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseSnap($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Inquiry Balance fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }


    private function rejectSnapPayout()
    {

        $this->niceLogger->info($this->logPrefix . 'rejectSnapPayout ');
        $config = $this->nicepayConfig;


        $requestBody = Cancel::builder()
            ->setMerchantId($config->getPartnerId())
            ->setOriginalReferenceNo($this->get('tXid'))
            ->setOriginalPartnerReferenceNo($this->get('originalPartnerReferenceNo'))
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $payoutService = new SnapPayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Reject Snap Payout with request ', ['requestBody' => $requestBody->toArray()]);
            $response = $payoutService->reject($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Reject Snap Payout Response ', ['response' => $response->getResponseMessage()]);
            return $this->handleResponseSnap($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Reject Payout fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }


    private function cancelSnapPayout()
    {

        $this->niceLogger->info($this->logPrefix . 'cancelSnapPayout ');
        $config = $this->nicepayConfig;


        $requestBody = Cancel::builder()
            ->setMerchantId($config->getPartnerId())
            ->setOriginalReferenceNo($this->get('tXid'))
            ->setOriginalPartnerReferenceNo($this->get('orderId'))
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $payoutService = new SnapPayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Cancel Snap Payout with reference no ', ['requestBody' => $requestBody->getOriginalPartnerReferenceNo()]);
            $response = $payoutService->cancel($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Cancel Snap Payout Response ', ['response' => $response->getResponseMessage()]);
            return $this->handleResponseSnap($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Cancel Payout fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function rejectV2Payout()
    {

        $this->niceLogger->info($this->logPrefix . 'rejectV2Payout ');
        $config = $this->nicepayConfig;

        $timeStamp = Helper::getFormattedTimestampV2();
        $tXid = $this->get('tXid');
        $iMid = $config->getPartnerId();

        $requestBody = Cancel::builder()
            ->setIMid($config->getPartnerId())
            ->setTimeStamp($timeStamp)
            ->setTXid($tXid)
            ->setMerchantTokenPayout($timeStamp, $iMid, $tXid, $config->getClientSecret())
            ->build();

        try {
            $payoutService = new V2PayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Reject V2 Payout with reference no ', ['requestBody' => $requestBody->toArrayV2()]);
            $response = $payoutService->rejectTransaction($requestBody);
            $this->niceLogger->info($this->logPrefix . 'Call Reject V2 Payout Response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseV2($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Reject fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function cancelV2Payout()
    {

        $this->niceLogger->info($this->logPrefix . 'cancelV2Payout ');
        $config = $this->nicepayConfig;

        $timeStamp = Helper::getFormattedTimestampV2();
        $tXid = $this->get('tXid');
        $iMid = $config->getPartnerId();

        $requestBody = Cancel::builder()
            ->setIMid($config->getPartnerId())
            ->setTimeStamp($timeStamp)
            ->setTXid($tXid)
            ->setMerchantTokenPayout($timeStamp, $iMid, $tXid, $config->getClientSecret())
            ->build();

        try {
            $payoutService = new V2PayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Cancel V2 Payout with reference no ', ['requestBody' => $requestBody->toArrayV2()]);
            $response = $payoutService->cancelTransaction($requestBody);
            $this->niceLogger->info($this->logPrefix . 'Call Cancel V2 Payout Response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseV2($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Reject fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }


    private function registSnapPayout()
    {

        $this->niceLogger->info($this->logPrefix . 'registSnapPayout ');
        $config = $this->nicepayConfig;

        $requestBody = Payout::builder()
            ->merchantId($config->getPartnerId())
            ->beneficiaryAccountNo($this->get('accountNo'))
            ->beneficiaryName($this->get('benefNm'))
            ->beneficiaryPhone($this->get('benefPhone'))
            ->beneficiaryCustomerResidence($this->get('benefStatus'))
            ->beneficiaryCustomerType($this->get('benefType'))
            ->beneficiaryPostalCode($this->get('benefPostCode'))
            ->payoutMethod($this->get('payoutMethod'))
            ->beneficiaryBankCode($this->get('bankCd'))
            ->amount($this->get('amt') . ".00", "IDR")
            ->partnerReferenceNo($this->get('orderId'))
            ->description($this->get('description'))
            ->reservedDt("")
            ->reservedTm("")
            ->build();

        $accessToken = self::getAccessToken($config);

        try {
            $payoutService = new SnapPayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Regist Snap Payout with reference no ', ['requestBody' => $requestBody->toArray()]);
            $response = $payoutService->registration($requestBody, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'Call Regist Snap Payout Response ', ['response' => $response->getResponseMessage()]);
            return $this->handleResponseSnap($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function registV2Payout()
    {
        $this->niceLogger->info($this->logPrefix . 'registV2Payout');
        $config = $this->nicepayConfig;

        $timeStamp = Helper::getFormattedTimestampV2();

        $iMid = $config->getPartnerId();
        $merchantKey = $config->getClientSecret();
        $amount = $this->get('amt');
        $reffNo = $this->get('orderId');
        $accountNo = $this->get('accountNo');

        $payout = Payout::builder()
            ->iMid($iMid)
            ->timeStamp($timeStamp)
            ->merchantToken($timeStamp, $iMid, $amount, $accountNo, $merchantKey)
            ->accountNo($accountNo)
            ->benefNm($this->get('benefNm'))
            ->benefType($this->get('benefType'))
            ->benefStatus($this->get('benefStatus'))
            ->bankCd($this->get('bankCd'))
            ->amt($this->get('amt'))
            ->referenceNo($reffNo)
            ->reservedDt($this->get('reservedDt') ?? "")
            ->reservedTm($this->get('reservedTm') ?? "")
            ->benefPhone($this->get('benefPhone'))
            ->description($this->get('description'))
            ->payoutMethod($this->get('payoutMethod') ?? "0")
            ->msId($this->get('msId') ?? 0)
            ->build();

        try {
            $payoutService = new V2PayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Payout with reference no ', ['requestBody' => $payout->toArrayV2()]);
            $response = $payoutService->registration($payout);
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Payout Response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseV2($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function registV2Redirect()
    {
        $this->niceLogger->info($this->logPrefix . 'registV2Redirect');
        $config = $this->nicepayConfig;

        $timeStamp = Helper::getFormattedTimestampV2();


        $iMid = $config->getPartnerId();
        $merchantKey = $config->getClientSecret();
        $amount = $this->get('amt');
        $reffNo = $this->get('orderId');
        $storeId = $this->getPaymentConfig('qris', 'store_id') ?? "";
        $nicepayBaseUrl = str_replace('/nicepay/', '/', $this->nicepayConfig->getNicepayBaseUrl());

        $paymethodCd = "00";

        $redirect = Redirect::builder()
            ->setIMid($iMid)
            ->setTimeStamp($timeStamp)
            ->setMerchantToken($timeStamp, $iMid, $reffNo, $amount, $merchantKey)
            ->setPayMethod($paymethodCd)
            ->setCurrency("IDR")
            ->setAmt($amount)
            ->setReferenceNo($reffNo)
            ->setGoodsNm($this->get('goodsNm'))
            ->setBillingNm($this->get('billingNm'))
            ->setBillingPhone($this->get('billingPhone'))
            ->setBillingEmail($this->get('billingEmail'))
            ->setBillingCity($this->get('billingCity'))
            ->setBillingState($this->get('billingState'))
            ->setBillingPostCd($this->get('billingPostCd'))
            ->setBillingCountry($this->get('billingCountry'))
            ->setDeliveryAddr($this->get('deliveryAddr'))
            ->setDeliveryNm($this->get('deliveryNm'))
            ->setDeliveryPhone($this->get('deliveryPhone'))
            ->setDeliveryCity($this->get('deliveryCity'))
            ->setDeliveryState($this->get('deliveryState'))
            ->setDeliveryPostCd($this->get('deliveryPostCd'))
            ->setDeliveryCountry($this->get('deliveryCountry'))
            ->setDbProcessUrl($this->dbProcessUrl)
            ->setCallBackUrl($nicepayBaseUrl . "IONPAY_CLIENT/paymentResult.jsp")
            ->setDescription($this->get('description'))
            ->setReqDomain($this->get('reqDomain'))
            ->setReqServerIP($this->get('reqServerIP'))
            ->setUserIP($this->get('userIP'))
            ->setUserAgent($this->get('userAgent'))
            ->setUserSessionID($this->get('userSessionID'))
            ->setUserLanguage("ko-KR,en-US;q=0.8,ko;q=0.6,en;q=0.4")
            ->setCartData($this->get('cartData'))
            ->setBankCd('')
            ->setVacctValidDt('')
            ->setVacctValidTm('')
            ->setMerFixAcctId('')
            ->setVat('')
            ->setFee('')
            ->setNoTaxAmt('')
            ->setReqDt("")
            ->setReqTm("")
            ->setReqClientVer('1.0.0')
            ->setSellers($this->get('sellers'))
            ->setMitraCd('')
            ->setInstmntType('2')
            ->setInstmntMon('1')
            ->setRecurrOpt('1')
            ->setPayValidDt('')
            ->setPaymentExpTm('')
            ->setPaymentExpDt('')
            ->setPayValidTm('')
            ->setShopId("")
            ->build();

        try {
            $payoutService = new V2RedirectService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Redirect with Request body : ', ['requestBody' => $redirect->toArrayV2()]);
            $response = $payoutService->registration($redirect);
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 Redirect Response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseV2($response, $paymethodCd);
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function approveV2Payout()
    {
        $this->niceLogger->info($this->logPrefix . 'approveV2Payout');
        $config = $this->nicepayConfig;

        $timeStamp = Helper::getFormattedTimestampV2();
        $iMid = $config->getPartnerId();
        $merchantKey = $config->getClientSecret();
        $tXid = $this->get('tXid');


        $payout = Payout::builder()
            ->iMid($iMid)
            ->timeStamp($timeStamp)
            ->merchantTokenPayoutAction($timeStamp, $iMid, $tXid, $merchantKey)
            ->tXid($tXid)
            ->build();

        try {
            $payoutService = new V2PayoutService($config);
            $this->niceLogger->info($this->logPrefix . 'Call Approve V2 Payout with Request ', ['requestBody' => $payout->toArrayV2()]);
            $response = $payoutService->approveTransaction($payout);
            $this->niceLogger->info($this->logPrefix . 'Call Approve V2 Payout Response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseV2($response, '07');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'Approve fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }


    public function registSnapVirtualAccount()
    {
        $this->niceLogger->info($this->logPrefix . 'registSnapVirtualAccount ');

        $reffNo = $this->get('orderId');

        $parameter = VirtualAccount::builder()
            ->setPartnerServiceId("")
            ->setCustomerNo("")
            ->setVirtualAccountNo("")
            ->setVirtualAccountName($this->get('billingNm'))
            ->setTrxId($reffNo)
            ->setTotalAmount($this->get('amt') . '.00', 'IDR')
            ->setAdditionalInfo([
                'bankCd' =>  $this->get('bankCd'),
                'goodsNm' => $this->get('goodsNm'),
                'dbProcessUrl' => $this->snapDbProcessUrl,
            ])
            ->build();


        $accessToken = $this->getAccessToken($this->nicepayConfig);
        $this->niceLogger->info($this->logPrefix . 'registSnapVirtualAccount Request ', ['requestBody' => $parameter->toArray()]);
        $snapVAService = new SnapVAService($this->nicepayConfig);

        try {
            $responseNicepay = $snapVAService->generateVA($parameter, $accessToken);
            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ', ['response' => $responseNicepay->getResponseMessage()]);

            return $this->handleResponseSnap($responseNicepay, '02');
        } catch (NicepayError $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    private function getDefaultExceptionResponse($eMsg)
    {
        return [
            'status' => $eMsg,
            'status_code' => '9999',
        ];
    }

    public function getAccessToken($config)
    {

        $tokenBody = AccessToken::builder()
            ->setGrantType('client_credentials')
            ->setAdditionalInfo([])
            ->build();

        $snap = new Snap($config);

        try {
            $response = $snap->requestSnapAccessToken($tokenBody);
        } catch (NicepayError $e) {
            $this->niceLogger->error($this->logPrefix . 'getAccessToken fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }

        return $response->getAccessToken();
    }

    public function registSnapEwallet()
    {

        $this->niceLogger->info($this->logPrefix . 'registSnapEwallet START');


        $nicepayBaseUrl = str_replace('/nicepay/', '/', $this->nicepayConfig->getNicepayBaseUrl());

        $requestBody = Ewallet::builder()
            ->partnerReferenceNo($this->get('orderId'))
            ->merchantId($this->nicepayConfig->getPartnerId())
            ->subMerchantId("")
            ->externalStoreId("")
            ->validUpTo("")
            ->pointOfInitiation("Web App")
            ->amount($this->get('amt') . '.00', "IDR")
            ->additionalInfo(
                [
                    "mitraCd" => $this->get('mitraCd'),
                    "goodsNm" => $this->get('goodsNm'),
                    "billingNm" => $this->get('billingNm'),
                    "billingPhone" => $this->get('billingPhone'),
                    "dbProcessUrl" => $this->snapDbProcessUrl,
                    "callBackUrl" => $nicepayBaseUrl . "IONPAY_CLIENT/paymentResult.jsp",
                    "msId" => "",
                    "cartData" => $this->get('cartData'),
                    "mbFee" => "",
                    "mbFeeType" => "",

                ]
            )
            ->urlParam([
                [$this->snapDbProcessUrl, "PAY_NOTIFY", "Y"],
                [$nicepayBaseUrl . "IONPAY_CLIENT/paymentResult.jsp", "PAY_RETURN", "Y"]
            ])
            ->build();

        $this->niceLogger->info($this->logPrefix . 'registSnapEwallet requestBody for order', ['requestBody' => $requestBody->toArray()]);

        $ewalletService = new SnapEwalletService($this->nicepayConfig);
        try {

            $accessToken = self::getAccessToken($this->nicepayConfig);

            $this->niceLogger->info($this->logPrefix . 'registSnapEwallet accessToken get', ['accessToken' => $accessToken !== null]);

            $response = $ewalletService->paymentEwallet($requestBody, $accessToken);

            $this->niceLogger->info($this->logPrefix . 'registSnapEwallet completed', ['response' => $response->getResponseMessage()]);
            return $this->handleResponseSnap($response, '05');
        } catch (Exception $e) {

            $this->niceLogger->info($this->logPrefix . 'registSnapEwallet error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function registSnapQris()
    {

        $this->niceLogger->info($this->logPrefix . 'registSnapQris ');
        $accessToken = self::getAccessToken($this->nicepayConfig);

        $iMid = $this->nicepayConfig->getPartnerId();
        $storeId = $this->getPaymentConfig('qris', 'store_id');
        $mitraCd = $this->getPaymentConfig('qris', 'mitra_cd');

        $reffNo = $this->get('orderId');
        $requestBody = Qris::builder()
            ->partnerReferenceNo($reffNo)
            ->amount($this->get('amt') . '.00', $this->get('currency'))
            ->merchantId($iMid)
            ->storeId($storeId)
            ->validityPeriod(Helper::getCustomTimeStamp('Y-m-d\TH:i:sP', 15))
            ->additionalInfo(
                $this->get('goodsNm'),
                $this->get('billingNm'),
                $this->get('billingPhone'),
                $this->get('billingEmail'),
                $this->get('billingCity'),
                $this->get('billingAddr'),
                $this->get('billingState'),
                $this->get('billingPostCd'),
                $this->get('billingCountry'),
                $this->nicepayConfig->getNicepayBaseUrl() . 'IONPAY_CLIENT/paymentResult.jsp',
                $this->snapDbProcessUrl,
                $this->get('userIP'),
                $this->get('cartData'),
                $mitraCd
            )
            ->build();

        $this->niceLogger->info($this->logPrefix . 'registSnapQris Request ', ['requestBody' => $requestBody->toArray()]);
        $qrisService = new SnapQrisService($this->nicepayConfig);

        try {
            $response = $qrisService->generateQris($requestBody, $accessToken);

            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ', ['response' => $response->getResponseMessage()]);
            return $this->handleResponseSnap($response, '08');
        } catch (Exception $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function registV2Card()
    {
        $this->niceLogger->info($this->logPrefix . 'registV2Card ');

        $timestamp = DateHelper::getFormattedTimestampV2();

        $iMid = $this->nicepayConfig->getPartnerId();
        $reffNo = $this->get('orderId');
        $amount = $this->get('amt');

        $parameter = Card::builder()
            ->timeStamp($timestamp)
            ->iMid($iMid)
            ->payMethod("01")
            ->currency("IDR")
            ->amt($amount)
            ->referenceNo($reffNo)
            ->merchantToken($timestamp, $iMid, $reffNo, $amount, $this->nicepayConfig->getClientSecret())
            ->description($this->get('description'))
            ->goodsNm($this->get('goodsNm'))
            ->billingNm($this->get('billingNm'))
            ->billingPhone($this->get('billingPhone'))
            ->billingEmail($this->get('billingEmail'))
            ->billingAddr($this->get('billingAddr'))
            ->billingCity($this->get('billingCity'))
            ->billingState($this->get('billingState'))
            ->billingPostCd($this->get('billingPostCd'))
            ->billingCountry($this->get('billingCountry'))
            ->dbProcessUrl($this->dbProcessUrl)
            ->cartData($this->get('cartData'))
            ->userIP($this->get('userIP'))
            ->instmntType("1")
            ->instmntMon("1")
            ->recurrOpt("")
            ->userLanguage("ko-KR,en-US;q=0.8,ko;q=0.6,en;q=0.4")
            ->userAgent($this->get('userAgent'))
            ->build();


        $this->niceLogger->info($this->logPrefix . 'registV2Card Request ', ['requestBody' => $parameter->toArrayV2()]);
        $v2CardService = new V2CardService($this->nicepayConfig);


        try {
            $response = $v2CardService->registration($parameter);
            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseV2($response, '01');
        } catch (NicepayError $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function registV2VirtualAccount()
    {
        $this->niceLogger->info($this->logPrefix . 'registV2VirtualAccount ');

        $timestamp = DateHelper::getFormattedTimestampV2();

        $iMid = $this->nicepayConfig->getPartnerId();
        $reffNo = $this->get('orderId');
        $amount = $this->get('amt');

        $virtualAccountBuilder = VirtualAccount::builder();
        $parameter = $virtualAccountBuilder
            ->setTimeStamp($timestamp)
            ->setIMid($iMid)
            ->setPayMethod($this->get('payMethod'))
            ->setCurrency($this->get('currency'))
            ->setBankCd($this->get('bankCd'))
            ->setAmt($amount)
            ->setReferenceNo($reffNo)
            ->setMerchantToken($timestamp, $iMid, $reffNo, $amount, $this->nicepayConfig->getClientSecret())
            ->setVacctValidDt("")
            ->setVacctValidTm("")
            ->setMerFixAcctId("")
            ->setDbProcessUrl($this->dbProcessUrl)
            ->setGoodsNm($this->get('goodsNm'))
            ->setCartData("{}")
            ->setBillingNm($this->get('billingNm'))
            ->setBillingPhone($this->get('billingPhone'))
            ->setBillingEmail($this->get('billingEmail'))
            ->setBillingAddr($this->get('billingAddr'))
            ->setBillingCity($this->get('billingCity'))
            ->setBillingState($this->get('billingState'))
            ->setBillingPostCd($this->get('billingPostCd'))
            ->setBillingCountry($this->get('billingCountry'))
            ->build();


        $this->niceLogger->info($this->logPrefix . 'registSnapVirtualAccount Request Body ', ['parameter' => $parameter->toArrayV2()]);
        $v2VaService = new V2VAService($this->nicepayConfig);


        try {
            $response = $v2VaService->registration($parameter);

            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ', ['response' => $response->getResultMsg()]);
            return $this->handleResponseV2($response, '02');
        } catch (NicepayError $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function registV2Ewallet()
    {

        $this->niceLogger->info($this->logPrefix . 'registV2Ewallet ');

        $timestamp = DateHelper::getFormattedTimestampV2();
        $iMid = $this->nicepayConfig->getPartnerId();
        $reffNo = $this->get('orderId');
        $amount = $this->get('amt');

        $mitraCd = $this->get('mitraCd');
        $billingPhone = $this->get('billingPhone');

        $parameter = Ewallet::builder()
            ->timeStamp($timestamp)
            ->iMid($iMid)
            ->payMethod($this->get('payMethod'))
            ->currency($this->get('currency'))
            ->amt($amount)
            ->referenceNo($reffNo)
            ->goodsNm($this->get('goodsNm'))
            ->billingNm($this->get('billingNm'))
            ->billingPhone($billingPhone)
            ->billingEmail($this->get('billingEmail'))
            ->billingAddr($this->get('billingAddr'))
            ->billingCity($this->get('billingCity'))
            ->billingState($this->get('billingState'))
            ->billingPostCd($this->get('billingPostCd'))
            ->billingCountry($this->get('billingCountry'))
            ->deliveryNm($this->get('deliveryNm'))
            ->deliveryPhone($this->get('deliveryPhone'))
            ->deliveryAddr($this->get('deliveryAddr'))
            ->deliveryCity($this->get('deliveryCity'))
            ->deliveryState($this->get('deliveryState'))
            ->deliveryPostCd($this->get('deliveryPostCd'))
            ->deliveryCountry($this->get('deliveryCountry'))
            ->dbProcessUrl($this->dbProcessUrl)
            ->merchantToken($timestamp, $iMid, $reffNo, $amount, $this->nicepayConfig->getClientSecret())
            ->reqDomain($this->get('reqDomain'))
            ->reqServerIP($this->get('reqServerIP'))
            ->reqClientVer('1.0.0')
            ->userIP($this->get('userIP'))
            ->userAgent($this->get('userAgent'))
            ->userSessionID($this->get('userSessionID'))
            ->userLanguage("ko-KR,en-US;q=0.8,ko;q=0.6,en;q=0.4")
            ->cartData($this->get('cartData'))
            ->mitraCd($mitraCd)
            ->build();

        $this->niceLogger->info($this->logPrefix . 'call Nicepay service Ewallet V2');

        $v2EwalletService = new V2EwalletService($this->nicepayConfig);

        try {
            $this->niceLogger->info($this->logPrefix . 'registSnapVirtualAccount request ', ['requestBody' => $parameter->toArrayV2()]);
            $response = $v2EwalletService->registration($parameter);
            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ');

            return $this->handleResponseV2($response, '05');
        } catch (NicepayError $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function registV2Cvs()
    {

        $this->niceLogger->info($this->logPrefix . 'registV2Cvs ');

        $timestamp = DateHelper::getFormattedTimestampV2();
        $iMid = $this->nicepayConfig->getPartnerId();
        $reffNo = $this->get('orderId');
        $amount = $this->get('amt');

        $mitraCd = $this->get('mitraCd');

        $parameter = CvS::builder()
            ->timeStamp($timestamp)
            ->iMid($iMid)
            ->payMethod("03")
            ->currency("IDR")
            ->amt($amount)
            ->referenceNo($reffNo)
            ->goodsNm($this->get('goodsNm'))
            ->billingNm($this->get('billingNm'))
            ->billingPhone($this->get('billingPhone'))
            ->billingEmail($this->get('billingEmail'))
            ->billingAddr($this->get('billingAddr'))
            ->billingCity($this->get('billingCity'))
            ->billingState($this->get('billingState'))
            ->billingPostCd($this->get('billingPostCd'))
            ->billingCountry($this->get('billingCountry'))
            ->dbProcessUrl($this->dbProcessUrl)
            ->description($this->get('description'))
            ->merchantToken($timestamp, $iMid, $reffNo, $amount, $this->nicepayConfig->getClientSecret())
            ->userIP($this->get('userIP'))
            ->mitraCd($mitraCd)
            ->cartData($this->get('cartData'))
            ->payValidDt("")
            ->payValidTm("")
            ->build();

        $this->niceLogger->info($this->logPrefix . 'call Nicepay service Conveninece Store V2');

        $v2CvsService = new V2CvsService($this->nicepayConfig);

        try {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 cvs with Request body : ', ['requestBody' => $parameter->toArrayV2()]);

            $response = $v2CvsService->registration($parameter);
            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ');

            return $this->handleResponseV2($response, '03');
        } catch (NicepayError $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function registV2Payloan()
    {

        $this->niceLogger->info($this->logPrefix . 'registV2Payloan ');

        $timestamp = DateHelper::getFormattedTimestampV2();
        $iMid = $this->nicepayConfig->getPartnerId();
        $reffNo = $this->get('orderId');
        $amount = $this->get('amt');

        $mitraCd = $this->get('mitraCd');

        $parameter = Payloan::builder()
            ->setTimeStamp($timestamp)
            ->setIMid($iMid)
            ->setPayMethod("06")
            ->setCurrency("IDR")
            ->setAmt($amount)
            ->setReferenceNo($reffNo)
            ->setGoodsNm($this->get('goodsNm'))
            ->setBillingNm($this->get('billingNm'))
            ->setBillingPhone($this->get('billingPhone'))
            ->setBillingEmail($this->get('billingEmail'))
            ->setBillingAddr($this->get('billingAddr'))
            ->setBillingCity($this->get('billingCity'))
            ->setBillingState($this->get('billingState'))
            ->setBillingPostCd($this->get('billingPostCd'))
            ->setBillingCountry($this->get('billingCountry'))
            ->setDeliveryAddr($this->get('deliveryAddr'))
            ->setDeliveryNm($this->get('deliveryNm'))
            ->setDeliveryPhone($this->get('deliveryPhone'))
            ->setDeliveryCity($this->get('deliveryCity'))
            ->setDeliveryState($this->get('deliveryState'))
            ->setDeliveryPostCd($this->get('deliveryPostCd'))
            ->setDeliveryCountry($this->get('deliveryCountry'))
            ->setDbProcessUrl($this->dbProcessUrl)
            ->setDescription($this->get('description'))
            ->setReqDomain($this->get('reqDomain'))
            ->setReqServerIP($this->get('reqServerIP'))
            ->setReqClientVer("1.0.0")
            ->setMerchantToken($timestamp, $iMid, $reffNo, $amount, $this->nicepayConfig->getClientSecret())
            ->setUserAgent($this->get('userAgent'))
            ->setUserLanguage("ko-KR,en-US;q=0.8,ko;q=0.6,en;q=0.4")
            ->setUserSessionID($this->get('userSessionID'))
            ->setUserIP($this->get('userIP'))
            ->setMitraCd($mitraCd)
            ->setCartData($this->get('cartData'))
            ->setSellers($this->get('sellers'))
            ->setInstmntType("2")
            ->setInstmntMon("1")
            ->setPayValidDt("")
            ->setPayValidTm("")
            ->build();


        $this->niceLogger->info($this->logPrefix . 'call Nicepay service Payloan V2');

        $v2PayloanService = new V2PayloanService($this->nicepayConfig);

        try {
            $this->niceLogger->info($this->logPrefix . 'Call Regist V2 payloan with Request body : ', ['requestBody' => $parameter->toArrayV2()]);

            $response = $v2PayloanService->registration($parameter);
            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ');

            return $this->handleResponseV2($response, '06');
        } catch (NicepayError $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function registV2VQris()
    {

        $this->niceLogger->info($this->logPrefix . 'registV2VQris ');
        $timestamp = DateHelper::getFormattedTimestampV2();

        $iMid = $this->nicepayConfig->getPartnerId();
        $reffNo = $this->get('orderId');
        $amount = $this->get('amt');

        $storeId = $this->getPaymentConfig('qris', 'store_id');
        $mitraCd = $this->getPaymentConfig('qris', 'mitra_cd');


        $parameter = Qris::builder()
            ->setTimeStamp($timestamp)
            ->setIMid($iMid)
            ->setPayMethod($this->get('payMethod'))
            ->setCurrency($this->get('currency'))
            ->setAmt($amount)
            ->setReferenceNo($reffNo)
            ->setMerchantToken($timestamp, $iMid, $reffNo, $amount, $this->nicepayConfig->getClientSecret())
            ->setDbProcessUrl($this->dbProcessUrl)
            ->setGoodsNm($this->get('goodsNm'))
            ->setCartData($this->get('cartData'))
            ->setBillingNm($this->get('billingNm'))
            ->setBillingPhone($this->get('billingPhone'))
            ->setBillingEmail($this->get('billingEmail'))
            ->setBillingCity($this->get('billingCity'))
            ->setBillingState($this->get('billingState'))
            ->setBillingPostCd($this->get('billingPostCd'))
            ->setBillingCountry($this->get('billingCountry'))
            ->setPaymentExpDt("")
            ->setPaymentExpTm("")
            ->setUserIP($this->get('userIP'))
            ->setShopId($storeId)
            ->setMitraCd($mitraCd)
            ->build();

        $this->niceLogger->info($this->logPrefix . 'call Nicepay service Qris V2');
        $v2QrisService = new V2QrisService($this->nicepayConfig);

        try {
            $this->niceLogger->info($this->logPrefix . 'registSnapVirtualAccount request :', ['requestBody' => $parameter->toArrayV2()]);
            $response = $v2QrisService->registration($parameter);
            $this->niceLogger->info($this->logPrefix . 'regist success call handle response ');

            return $this->handleResponseV2($response, '08');
        } catch (NicepayError $e) {
            $this->niceLogger->info($this->logPrefix . 'regist fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    public function getPaymentConfig($paymentCode, $field, $storeId = null)
    {
        $path = 'payment/' . $paymentCode . '/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function statusInquiryV2()
    {
        $this->niceLogger->info($this->logPrefix . 'statusInquiryV2 ');

        $timeStamp = $this->get('timeStamp');
        $reffNo = $this->get('referenceNo');
        $amount = $this->get('amt');
        $tXid = $this->get('tXid');
        $iMid = $this->nicepayConfig->getPartnerId();


        $parameter = InquiryStatus::builder()
            ->setTimeStamp($timeStamp)
            ->setTxId($tXid)
            ->setIMid($iMid)
            ->setMerchantToken($timeStamp, $iMid, $reffNo, $amount, $this->nicepayConfig->getClientSecret())
            ->setReferenceNo($reffNo)
            ->setAmt($amount)
            ->build();

        try {

            $v2Service = new BaseV2Service($this->nicepayConfig);
            $this->niceLogger->info($this->logPrefix . 'statusInquiryV2 request body ', ['parameter' => $parameter->toArrayV2()]);
            $response = $v2Service->inquiryStatus($parameter);

            $this->niceLogger->info($this->logPrefix . 'statusInquiryV2 success call handle response ');
            return $this->handleResponseInquiryStatus($response, "00");
        } catch (NicepayError $e) {

            $this->niceLogger->info($this->logPrefix . 'statusInquiryV2 fail with error ' . $e->getMessage());
            return $this->getDefaultExceptionResponse($e->getMessage());
        }
    }

    function handleResponseInquiryStatus($response, $payMethod)
    {

        $isSnap = $this->apiVersion == 'snap' ? true : false;
        $this->niceLogger->info($this->logPrefix . 'handleResponseInquiryStatus isSnap ' . $isSnap);

        $data = array();

        $responseCode = $isSnap ? $response->getResponseCode() : $response->getResultCd();
        $isSuccess = $responseCode == '0000' || strpos($responseCode, '200') === 0;

        if ($isSnap && $payMethod == '02' && $isSuccess) {

            $this->niceLogger->info($this->logPrefix . 'handleResponseInquiryStatus Success VA Snap');

            $virtualAccountData = $response->getVirtualAccountData() ?? [];
            $additionalInfo = $response->getAdditionalInfo() ?? [];

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $additionalInfo['tXidVA'] ?? '',
                'amount' => $additionalInfo['totalAmount']['value'] ?? '',
                'virtual_account_no' => $virtualAccountData['virtualAccountNo'] ?? '',
                'bank_code' => $additionalInfo['bankCd'] ?? '',
                'status_trx' => $additionalInfo['latestTransactionStatus'] ?? '',
            ];
        } else if ($isSnap && $payMethod == '05' && $isSuccess) {
            $this->niceLogger->info($this->logPrefix . 'handleResponseInquiryStatus Success Ewallet Snap');

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $response->getOriginalReferenceNo() ?? '',
                'referenceNo' => $response->getOriginalPartnerReferenceNo() ?? "",
                'amount' => $response->getAmount()['value'] ?? '',
                'status_trx' => $response->getLatestTransactionStatus() ?? '',
            ];
        } else if ($isSnap && $payMethod == '07' && $isSuccess) {
            $this->niceLogger->info($this->logPrefix . 'handleResponseInquiryStatus Success Payout Snap');

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $response->getOriginalReferenceNo() ?? '',
                'referenceNo' => $response->getOriginalPartnerReferenceNo() ?? "",
                'amount' => $response->getAmount()['value'] ?? '',
                'status_trx' => $response->getLatestTransactionStatus() ?? '',
            ];
        } else if ($isSnap && $payMethod == '08' && $isSuccess) {
            $this->niceLogger->info($this->logPrefix . 'handleResponseInquiryStatus Success Qris Snap');
            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $response->getOriginalReferenceNo() ?? '',
                'referenceNo' => $response->getOriginalPartnerReferenceNo() ?? "",
                'amount' => $response->getAmount()['value'] ?? '',
                'status_trx' => $response->getLatestTransactionStatus() ?? '',
            ];
        } else if ($isSnap) {
            $this->niceLogger->info($this->logPrefix . 'handleResponseSnap Snap Failed');
            $data = [
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
            ];
        } else if ($responseCode == '0000') {
            $this->niceLogger->info($this->logPrefix . 'handleResponseInquiryStatus V2 Success');
            $data = [
                'status' => $response->getResultMsg(),
                'status_code' => $response->getResultCd(),
                'tXid' => $response->getTXid(),
                'status_trx' => $response->getStatus(),
            ];
        } else {
            $this->niceLogger->info($this->logPrefix . ' handleResponseInquiryStatus V2 Failed');
            $data = [
                'status' => $response->getResultMsg(),
                'status_code' => $response->getResultCd(),
            ];
        }

        return $data;
    }

    function handleResponseSnap($response, $payMethod)
    {
        $this->niceLogger->info($this->logPrefix . 'handleResponseSnap ');

        $data = array();
        if ($payMethod == '02' && $response->getResponseCode() == '2002700') {
            $this->niceLogger->info($this->logPrefix . 'handleResponseSnap Success VA');

            $additionalInfo = $response->getVirtualAccountData()['additionalInfo'];

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $additionalInfo['tXidVA'],
                'amount' => $response->getVirtualAccountData()['totalAmount']['value'],
                'order_id' => $this->get('orderId'),
                // 'transaction_data' => [
                'virtual_account_no' => $response->getVirtualAccountData()['virtualAccountNo'],
                'bank_code' => $this->get('bankCd'),
                'paymentExp' => DateHelper::formatTimestampToStringDateTime($additionalInfo['vacctValidDt'] . $additionalInfo['vacctValidTm']),
                // ]
            ];
        } else if ($payMethod == '05' && $response->getResponseCode() == '2005400') {

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $response->getReferenceNo(),
                'amount' => '',
                'order_id' => $this->get('orderId'),
                'paymentExp' => '',
                'mitra_cd' => $this->get('mitraCd'),
                'payment_url' => $response->getWebRedirectUrl(),
            ];

            $this->niceLogger->info($this->logPrefix . 'handleResponseSnap Success Ewallet');
        } else if ($payMethod == '07' && $response->getResponseCode() == '2000000') {

            $this->niceLogger->info($this->logPrefix . ' handleResponseSnap Success Payout');

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $response->getOriginalReferenceNo(),
                'amount' => $response->getAmount()['value'],
                'order_id' => $this->get('orderId'),
                'beneficiary_name' => $response->getBeneficiaryName(),
                'bank_code' => $response->getBeneficiaryBankCode(),
                'account_no' => $response->getBeneficiaryaccountNo(),
                'payout_method' => $response->getPayoutMethod() ?? '',
                'transaction_status' => $response->getTransactionStatus() ?? '',
                'available_balance' => $response->getAccountInfos()[0]['availableBalance']['value'] ?? '',
                'iMid' => $response->getAccountNo() ?? '',

            ];
        } else if ($payMethod == '08' && $response->getResponseCode() == '2004700') {
            $this->niceLogger->info($this->logPrefix . 'handleResponseSnap Success Qris');

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
                'tXid' => $response->getReferenceNo(),
                'amount' => $this->get('amt'),
                'order_id' => $this->get('orderId'),
                // 'transaction_data' => [
                'paymentExp' => $response->getAdditionalInfo()['validityPeriod'],
                'qris_url' => $response->getQrUrl(),
                // ]
            ];
        } else {
            $this->niceLogger->info($this->logPrefix . 'handleResponseSnap Failed');
            $data = [
                'status' => $response->getResponseMessage(),
                'status_code' => $response->getResponseCode(),
            ];
        }

        $this->niceLogger->info($this->logPrefix . 'handleResponseSnap ' . json_encode($data));

        return $data;
    }

    function handleResponseV2($response, $payMethod)
    {
        $data = array();
        if ($response->getResultCd() == '0000') {
            $this->niceLogger->info($this->logPrefix . 'handleResponseV2 Success');

            // $additionalInfo = $response->getVirtualAccountData()['additionalInfo'];

            $data = [
                'payment_method' => $payMethod,
                'status' => $response->getResultMsg(),
                'status_code' => $response->getResultCd(),
                'tXid' => $response->getTXid(),
                'amount' => $response->getAmt(),
                'order_id' => $this->get('orderId'),
            ];


            if ($payMethod == '00') {

                $additionalData = [
                    'payment_url' => $response->getPaymentURL() . "?tXid=" . $response->getTXid(),

                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == '01') {

                // Get v2 payment url
                $timeStamp = DateHelper::getFormattedTimestampV2();
                $merchantTokenData = $timeStamp . $this->nicepayConfig->getPartnerId() . $response->getReferenceNo() . $response->getAmt() . $this->nicepayConfig->getClientSecret();

                $nicepayBaseUrl = str_replace('/nicepay/', '/', $this->nicepayConfig->getNicepayBaseUrl());

                $paymentUrl = $this->getPaymentUrl($timeStamp, $merchantTokenData, $response->getTXid(), $nicepayBaseUrl . "IONPAY_CLIENT/paymentResult.jsp");



                $additionalData = [
                    'payment_url' => $paymentUrl,
                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == '02') {


                $additionalData = [
                    'virtual_account_no' => $response->getVacctNo(),
                    'bank_code' => $response->getBankCd(),
                    'paymentExp' => DateHelper::formatTimestampToStringDateTime($response->getVacctValidDt() . $response->getVacctValidTm()),

                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == '03') {


                $additionalData = [
                    'cvs_pay_no' => $response->getPayNo(),
                    'mitra_code' => $response->getMitraCd(),
                    'paymentExp' => DateHelper::formatTimestampToStringDateTime($response->getPayValidDt() . $response->getPayValidTm()),
                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == '05') {

                // Get v2 payment url
                $timeStamp = DateHelper::getFormattedTimestampV2();
                $merchantTokenData = $timeStamp . $this->nicepayConfig->getPartnerId() . $response->getReferenceNo() . $response->getAmt() . $this->nicepayConfig->getClientSecret();

                $nicepayBaseUrl = str_replace('/nicepay/', '/', $this->nicepayConfig->getNicepayBaseUrl());

                $paymentUrl = $this->getPaymentUrl($timeStamp, $merchantTokenData, $response->getTXid(), $nicepayBaseUrl . "IONPAY_CLIENT/paymentResult.jsp");

                $additionalData = [
                    'mitra_cd' => $this->get('mitraCd'),
                    'payment_url' => $paymentUrl
                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == '06') {

                // Get v2 payment url
                $timeStamp = DateHelper::getFormattedTimestampV2();
                $merchantTokenData = $timeStamp . $this->nicepayConfig->getPartnerId() . $response->getReferenceNo() . $response->getAmt() . $this->nicepayConfig->getClientSecret();

                $nicepayBaseUrl = str_replace('/nicepay/', '/', $this->nicepayConfig->getNicepayBaseUrl());

                $paymentUrl = $this->getPaymentUrl($timeStamp, $merchantTokenData, $response->getTXid(), $nicepayBaseUrl . "IONPAY_CLIENT/paymentResult.jsp");

                $additionalData = [
                    'mitra_cd' => $this->get('mitraCd'),
                    'payment_url' => $paymentUrl
                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == '07') {

                $additionalData = [
                    'beneficiary_name' => $response->getBenefNm(),
                    'bank_code' => $response->getBankCd(),
                    'account_no' => $response->getAccountNo(),
                    'payout_method' => $response->getPayoutMethod(),
                    'trans_date' => $response->getTransDt(),
                    'trans_time' => $response->getTransTm(),
                    // For Cashout
                    'valid_date' => $response->getValidDate(),
                    'valid_time' => $response->getValidTime(),
                    'cashout_token' => $response->getCashoutToken(),
                    'm_code' => $response->getMCode(),
                    // For Balance
                    'balance' => $response->getBalance(),
                    'scheduled' => $response->getScheduled(),
                    'transaction_status' => $response->getStatus(),
                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == '08') {
                $additionalData = [
                    'qris_url' => $response->getQrUrl(),
                    'paymentExp' => DateHelper::formatTimestampToStringDateTime($response->getPaymentExpDt() . $response->getPaymentExpTm()),
                ];

                $data = array_merge($data, $additionalData);
            } else if ($payMethod == 'inquiry') {
                $additionalData = [
                    'status_trx' => $response->getStatus(),
                ];

                $data = array_merge($data, $additionalData);
            }
        } else {
            $this->niceLogger->info($this->logPrefix . 'handleResponseV2 Failed');
            $data = [
                'status' => $response->getResultMsg(),
                'status_code' => $response->getResultCd(),
            ];
        }

        $this->niceLogger->info($this->logPrefix . 'handeleResponseV2 ', ['data' => $data]);

        return $data;
    }

    public function merchantTokenNotification()
    {
        // SHA256( Concatenate(iMid + referenceNo + amt + merchantKey) )
        return hash(
            'sha256',
            $this->get('iMid') .
                $this->get('tXid') .
                $this->get('amt') .
                $this->get('merchantKey')
        );
    }

    public function merchantTokenV2()
    {
        return hash('sha256', $this->get('timeStamp') . $this->get('iMid') . $this->get('referenceNo') . $this->get('amt') . $this->get('merchantKey'));
    }


    public function getPaymentUrl($timeStamp, $merchantTokenData, $tXid, $callBackUrl)
    {

        // Encode merchant Token 

        $merchantToken = Helper::generateMerchantToken($merchantTokenData);
        $nicepayPaymentUrl = $this->nicepayConfig->getNicepayBaseUrl() . NicepayCons::getV2PaymentEndpoint();

        $params = [
            'timeStamp' => $timeStamp,
            'merchantToken' => $merchantToken,
            'tXid' => $tXid,
            'callBackUrl' => $callBackUrl,
        ];

        if ($this->get('payMethod') == '01') {
            $additionalParams = [
                'referenceNo' => $this->get('orderId'),
                'callBackUrl' => $callBackUrl,
                'cardNo' => $this->get('cardNo'),
                'cardExpYymm' => $this->get('cardExpYymm'),
                'cardCvv' => $this->get('cardCvv'),
                'cardHolderNm' => $this->get('cardHolderNm'),
                'cardHolderEmail' => $this->get('billingEmail'),

            ];

            $params = array_merge($params, $additionalParams);
        }

        $queryString = http_build_query($params);


        return $nicepayPaymentUrl . '?' . $queryString;
    }
}
