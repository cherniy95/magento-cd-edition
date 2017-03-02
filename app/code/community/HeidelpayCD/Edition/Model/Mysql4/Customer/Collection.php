<?php
/**
 * Customer collection
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
class HeidelpayCD_Edition_Model_Mysql4_Customer_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('hcd/customer');
        parent::_construct();
    }
}
