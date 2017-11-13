<?php
/**
 * Debit card payment method
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
// @codingStandardsIgnoreLine magento marketplace namespace warning
class HeidelpayCD_Edition_Model_Payment_Hcddc extends HeidelpayCD_Edition_Model_Payment_Abstract
{

    /**
     * HeidelpayCD_Edition_Model_Payment_Hcddc constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_code = 'hcddc';
        $this->_canCapture = true;
        $this->_canCapturePartial = true;
        $this->_formBlockType = 'hcd/form_creditcard';
    }


    /**
     * @inheritdoc
     */
    public function isRecognition()
    {
        $path = "payment/".$this->_code."/";
        $storeId =  Mage::app()->getStore()->getId();
        return Mage::getStoreConfig($path.'recognition', $storeId);
    }

    /**
     * @inheritdoc
     */
    public function activeRedirect()
    {
        $recognation = $this->isRecognition();
        if ($recognation > 0) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getFormBlockType()
    {
        return $this->_formBlockType;
    }

    /**
     * @inheritdoc
     */
    public function chargeBack($order, $message = "")
    {
        $message = Mage::helper('hcd')->__('chargeback');
        return parent::chargeBack($order, $message);
    }
}
