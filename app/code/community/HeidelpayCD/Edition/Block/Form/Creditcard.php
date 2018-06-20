<?php
/**
 * Credit card block
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
 */
// @codingStandardsIgnoreLine magento marketplace namespace warning
class HeidelpayCD_Edition_Block_Form_Creditcard extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('hcd/form/creditcard.phtml');
    }
}
