<?php
/**
 * Yapital payment method
 *
 * This payment method is deprecated and exists for backwards compatibility purposes only.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/magento
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento
 * @category Magento
 *
 * @deprecated This payment method is not longer available
 */
class HeidelpayCD_Edition_Model_Payment_Hcdyt extends HeidelpayCD_Edition_Model_Payment_Abstract
{
    /**
     * HeidelpayCD_Edition_Model_Payment_Hcdyt constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_code = 'hcdyt';
        $this->_canRefund = false;
        $this->_canRefundInvoicePartial = false;
    }

    /**
     * Deactivate payment method.
     *
     * @param null|mixed $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return false;
    }
}
