<?php
/**
 * Invoice info block
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
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
class HeidelpayCD_Edition_Block_Info_Prepayment extends Mage_Payment_Block_Info
{
    public function toPdf()
    {
        $this->setTemplate('hcd/info/pdf/prepayment.phtml');
        return $this->toHtml();
    }
}
