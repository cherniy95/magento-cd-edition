<?php
/** @noinspection LongInheritanceChainInspection */
/**
 * Abstract payment method
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento
 * @category Magento
 */
class HeidelpayCD_Edition_Model_Payment_AbstractSecuredPaymentMethods extends HeidelpayCD_Edition_Model_Payment_Abstract
{
    /**
     * validation helper
     *
     * @var HeidelpayCD_Edition_Helper_Validator $_validatorHelper
     */
    protected $_validatorHelper;

    /**
     * Append invoice info text to customer email.
     *
     * @var bool $_sendsInvoiceMailComment
     */
    protected $_sendsInvoiceMailComment = false;

    /**
     * validated parameter
     *
     * @var array validated parameter
     */
    protected $_validatedParameters = array();

    /**
     * post data from checkout
     *
     * @var array $_postPayload post data from checkout
     */
    protected $_postPayload = array();

    /**
     * HeidelpayCD_Edition_Model_Payment_AbstractSecuredPaymentMethods constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_canBasketApi = false;
        $this->_infoBlockType = 'hcd/info_invoice';
        $this->_formBlockType = 'hcd/form_invoiceSecured';

        $this->_validatorHelper = Mage::helper('hcd/validator');
    }

    /**
     * is payment method available
     *
     * @param null $quote
     *
     * @return bool is payment method available
     */
    public function isAvailable($quote = null)
    {
        $billing = $this->getQuote()->getBillingAddress();
        $shipping = $this->getQuote()->getShippingAddress();

        /* billing and shipping address has to match */
        if (($billing->getFirstname() !== $shipping->getFirstname()) ||
            ($billing->getLastname() !== $shipping->getLastname()) ||
            ($billing->getStreet() !== $shipping->getStreet()) ||
            ($billing->getPostcode() !== $shipping->getPostcode()) ||
            ($billing->getCity() !== $shipping->getCity()) ||
            ($billing->getCountry() !== $shipping->getCountry())
        ) {
            return false;
        }

        /* payment method is b2c only */
        if (!empty($billing->getCompany())) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Validate customer input on checkout
     *
     * @return $this
     * @throws \Mage_Core_Exception
     */
    public function validate()
    {
        parent::validate();

        if (isset($this->_postPayload['method']) && $this->_postPayload['method'] === $this->_code) {
            if (array_key_exists($this->_code . '_salutation', $this->_postPayload)) {
                $this->_validatedParameters['NAME.SALUTATION'] =
                    (
                        $this->_postPayload[$this->_code . '_salutation'] === 'MR' ||
                        $this->_postPayload[$this->_code . '_salutation'] === 'MRS'
                    )
                        ? $this->_postPayload[$this->_code . '_salutation'] : '';
            }

            if (array_key_exists($this->_code . '_dobday', $this->_postPayload) &&
                array_key_exists($this->_code . '_dobmonth', $this->_postPayload) &&
                array_key_exists($this->_code . '_dobyear', $this->_postPayload)
            ) {
                $day = (int)$this->_postPayload[$this->_code . '_dobday'];
                $month = (int)$this->_postPayload[$this->_code . '_dobmonth'];
                $year = (int)$this->_postPayload[$this->_code . '_dobyear'];

                if ($this->_validatorHelper->validateDateOfBirth($day, $month, $year)) {
                    $this->_validatedParameters['NAME.BIRTHDATE']
                        = $year . '-' . sprintf('%02d', $month) . '-' . sprintf('%02d', $day);
                } else {
                    Mage::throwException(
                        $this->_getHelper()
                            ->__('The minimum age is 18 years for this payment method.')
                    );
                }
            }


            $this->saveCustomerData($this->_validatedParameters);
        }

        return $this;
    }

    /**
     * Payment information for invoice mail
     *
     * @param array $paymentData transaction response
     *
     * @return string return payment information text
     */
    public function showPaymentInfo($paymentData)
    {
        $loadSnippet = $this->_getHelper()->__('Invoice Info Text');

        $replace = array(
            '{AMOUNT}' => $paymentData['CLEARING_AMOUNT'],
            '{CURRENCY}' => $paymentData['CLEARING_CURRENCY'],
            '{CONNECTOR_ACCOUNT_HOLDER}' => $paymentData['CONNECTOR_ACCOUNT_HOLDER'],
            '{CONNECTOR_ACCOUNT_IBAN}' => $paymentData['CONNECTOR_ACCOUNT_IBAN'],
            '{CONNECTOR_ACCOUNT_BIC}' => $paymentData['CONNECTOR_ACCOUNT_BIC'],
            '{IDENTIFICATION_SHORTID}' => $paymentData['IDENTIFICATION_SHORTID'],
        );

        return strtr($loadSnippet, $replace);
    }

    /**
     * Handle transaction with means pending
     *
     * @param $order Mage_Sales_Model_Order
     * @param $data HeidelpayCD_Edition_Model_Transaction
     * @param $message string order history message
     *
     * @return Mage_Sales_Model_Order
     * @throws \Mage_Core_Exception
     */
    public function pendingTransaction($order, $data, $message = '')
    {
        $message = 'Heidelpay ShortID: ' . $data['IDENTIFICATION_SHORTID'] . ' ' . $message;

        /** @noinspection PhpUndefinedMethodInspection */
        $order->getPayment()
            ->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
            ->setParentTransactionId($order->getPayment()->getLastTransId())
            ->setIsTransactionClosed(false);

        /** @var Mage_Sales_Model_Service_Order $salesOrder */
        $salesOrder = Mage::getModel('sales/service_order', $order);

        /** @var Mage_Sales_Model_Convert_Order $convertOrder */
        $convertOrder = Mage::getModel('hcd/convert_order');
        $invoice = $salesOrder->setConvertor($convertOrder)->prepareInvoice();
        $invoice->register();
        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);

        /** @noinspection PhpUndefinedMethodInspection */
        $order->setIsInProcess(true);

        /** @noinspection PhpUndefinedMethodInspection */
        $invoice->setIsPaid(false);
        $order->addStatusHistoryComment(Mage::helper('hcd')->__('Automatically invoiced by Heidelpay.'));
        $invoice->save();
        if ($this->_invoiceOrderEmail) {
            $code = $order->getPayment()->getMethodInstance()->getCode();
            $invoiceMailComment = '';
            if ($code === 'hcdiv' || $this->isSendingInvoiceMailComment()) {
                /** @noinspection PhpUndefinedMethodInspection */
                $info = $order->getPayment()->getMethodInstance()->showPaymentInfo($data);
                $invoiceMailComment = ($info === false) ? '' : '<h3>'
                    . $this->_getHelper()->__('payment information') . '</h3><p>' . $info . '</p>';
            }

            $invoice->sendEmail(true, $invoiceMailComment); // send invoice mail
        }


        /** @noinspection PhpUndefinedMethodInspection */
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        /** @noinspection PhpUndefinedMethodInspection */
        $transactionSave->save();

        $this->log('Set transaction to processed and generate invoice');

        /** @noinspection PhpUndefinedMethodInspection */
        $order->setState(
            $order->getPayment()->getMethodInstance()->getStatusSuccess(false),
            $order->getPayment()->getMethodInstance()->getStatusSuccess(true),
            $message
        );

        $order->getPayment()->addTransaction(
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH,
            null,
            true,
            $message
        );

        return $order;
    }

    /**
     * Handle transaction with means processing
     *
     * @param $order Mage_Sales_Model_Order
     * @param $data HeidelpayCD_Edition_Model_Transaction
     * @param $message string order history message
     *
     * @return Mage_Sales_Model_Order
     * @throws \Mage_Core_Exception
     */
    public function processingTransaction($order, $data, $message = '')
    {
        /** @var  $paymentHelper HeidelpayCD_Edition_Helper_Payment */
        $paymentHelper = Mage::helper('hcd/payment');

        $message = ($message === '') ? 'Heidelpay ShortID: ' . $data['IDENTIFICATION_SHORTID'] : $message;
        $totallyPaid = false;

        /** @noinspection PhpUndefinedMethodInspection */
        $order->getPayment()
            ->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
            ->setParentTransactionId($order->getPayment()->getLastTransId())
            ->setIsTransactionClosed(true);

        if ($order->getOrderCurrencyCode() === $data['PRESENTATION_CURRENCY'] &&
            $paymentHelper->format($order->getGrandTotal()) === $data['PRESENTATION_AMOUNT']
        ) {
            /** @noinspection PhpUndefinedMethodInspection */
            $order->setState(
                $order->getPayment()->getMethodInstance()->getStatusSuccess(false),
                $order->getPayment()->getMethodInstance()->getStatusSuccess(true),
                $message
            );
            $totallyPaid = true;
        } else {
            // in case rc is ack and amount is to low or currency miss match
            /** @noinspection PhpUndefinedMethodInspection */
            $order->setState(
                $order->getPayment()->getMethodInstance()->getStatusPartlyPaid(false),
                $order->getPayment()->getMethodInstance()->getStatusPartlyPaid(true),
                $message
            );
        }

        // Set invoice to paid when the total amount matches
        if ($totallyPaid && $order->hasInvoices()) {

            /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoices */
            $invoices = $order->getInvoiceCollection();

            /** @var  $invoice Mage_Sales_Model_Order_Invoice */
            foreach ($invoices as $invoice) {
                $this->log('Set invoice ' . (string)$invoice->getIncrementId() . ' to paid.');
                /** @noinspection PhpUndefinedMethodInspection */
                $invoice
                    ->capture()
                    ->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID)
                    ->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)
                    ->setIsPaid(true)
                    // @codingStandardsIgnoreLine use of save in a loop
                    ->save();

                /** @noinspection PhpUndefinedMethodInspection */
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
            }
        }

        // Set total paid and invoice to the connector amount
        $order->setTotalInvoiced($data['PRESENTATION_AMOUNT']);
        $order->setTotalPaid($data['PRESENTATION_AMOUNT']);

        $order->getPayment()->addTransaction(
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
            null,
            true,
            $message
        );

        /** @noinspection PhpUndefinedMethodInspection */
        $order->setIsInProcess(true);

        return $order;
    }

    /**
     * @return bool
     */
    public function isSendingInvoiceMailComment()
    {
        return $this->_sendsInvoiceMailComment;
    }
}
