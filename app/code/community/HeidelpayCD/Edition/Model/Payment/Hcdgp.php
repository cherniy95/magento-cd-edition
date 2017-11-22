<?php
/** @noinspection LongInheritanceChainInspection */
/**
 * Giropay payment method
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
class HeidelpayCD_Edition_Model_Payment_Hcdgp extends HeidelpayCD_Edition_Model_Payment_Abstract
{
    /**
     * HeidelpayCD_Edition_Model_Payment_Hcdgp constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_code = 'hcdgp';
    }
}
