<?php

/**
 * Payment Helper
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
class HeidelpayCD_Edition_Helper_BasketApi extends HeidelpayCD_Edition_Helper_AbstractHelper
{

    /**
     * @param $quote Mage_Sales_Model_Quote quote object
     * @param $storeId int current store id
     * @param  $includingShipment boolean include
     *
     * @return array return basket api array
     */
    public function basketItems($quote, $storeId, $includingShipment = false)
    {
        $shoppingCartItems = $quote->getAllVisibleItems();

        $this->log('all basket items'. print_r($shoppingCartItems, 1));

        $shoppingCart = array(

            'authentication' => array(

                'login' => trim(Mage::getStoreConfig('hcd/settings/user_id', $storeId)),
                'sender' => trim(Mage::getStoreConfig('hcd/settings/security_sender', $storeId)),
                'password' => trim(Mage::getStoreConfig('hcd/settings/user_pwd', $storeId)),

            ),


            'basket' => array(
                'amountTotalNet' => floor(bcmul($quote->getGrandTotal(), 100, 10)),
                'currencyCode' => $quote->getGlobalCurrencyCode(),
                'amountTotalDiscount' => floor(bcmul($quote->getDiscountAmount(), 100, 10)),
                'itemCount' => count($shoppingCartItems)
            )


        );

        $count = 1;
        /** @var  $item Mage_Sales_Model_Order_Item */
        foreach ($shoppingCartItems as $item) {
            $shoppingCart['basket']['basketItems'][] = array(
                'position' => $count,
                'basketItemReferenceId' => $item->getItemId(),
                'quantity' => ($item->getQtyOrdered() !== false) ? floor($item->getQtyOrdered()) : $item->getQty(),
                'vat' => floor($item->getTaxPercent()),
                'amountVat' => floor(bcmul($item->getTaxAmount(), 100, 10)),
                'amountGross' => floor(bcmul($item->getRowTotalInclTax(), 100, 10)),
                'amountNet' => floor(bcmul($item->getRowTotal(), 100, 10)),
                'amountPerUnit' => floor(bcmul($item->getPrice(), 100, 10)),
                'amountDiscount' => floor(bcmul($item->getDiscountAmount(), 100, 10)),
                'type' => 'goods',
                'title' => $item->getName(),
                'imageUrl' => (string)Mage::helper('catalog/image')->init($item->getProduct(), 'thumbnail')
            );
            $count++;
        }

        if ($includingShipment) {
            $shoppingCart['basket']['basketItems'][] = array(
                'position' => $count,
                'basketItemReferenceId' => $count,
                "type" => "shipment",
                "title" => "Shipping",
                'quantity' => 1,
                'vat' => '',
                'amountVat' => '',
                'amountGross' => '',
                'amountNet' => '',
                'amountPerUnit' => '',
                'amountDiscount' => ''
            );
        }


        return $shoppingCart;
    }

}