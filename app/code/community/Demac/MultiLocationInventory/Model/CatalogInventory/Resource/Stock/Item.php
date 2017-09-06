<?php

/**
 * Class Demac_MultiLocationInventory_Model_CatalogInventory_Resource_Stock_Item
 */
class Demac_MultiLocationInventory_Model_CatalogInventory_Resource_Stock_Item extends Mage_CatalogInventory_Model_Resource_Stock_Item
{
    /**
     * Add join for catalog in stock field to product collection
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     *
     * @return Mage_CatalogInventory_Model_Resource_Stock_Item
     */
    public function addCatalogInventoryToProductCollection($productCollection)
    {
        $adapter = $this->_getReadAdapter();
        $isManageStock = (int)Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
        $stockExpr = $adapter->getCheckSql('cisi.use_config_manage_stock = 1', $isManageStock, 'cisi.manage_stock');
        $stockExpr = $adapter->getCheckSql("({$stockExpr} = 1)", 'cisi.is_in_stock', '1');

        $productCollection->joinTable(
            ['cisi' => 'cataloginventory/stock_item'],
            'product_id=entity_id',
            [
                'is_saleable' => new Zend_Db_Expr($stockExpr),
                'inventory_in_stock' => 'is_in_stock'
            ],
            null,
            'left'
        );

        $productCollection->getSelect()->joinLeft(
            array('ciss' => $this->getTable('cataloginventory/stock_status')),
            'ciss.product_id=e.entity_id' .
            ' AND ciss.website_id = ' . Mage::app()->getWebsite()->getId(),
            []
        );

        return $this;
    }

    /**
     * Perform actions after object load
     *
     * @param Varien_Object $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('cataloginventory/stock_status'))
            ->where('product_id = ?', intval($object->getProductId()))
            ->where('website_id = ?', intval(Mage::app()->getWebsite()->getId()));

        $data = $this->_getReadAdapter()->fetchRow($select);
        if ($data) {
            $object->setDataUsingMethod('qty', $data['qty']);
            $object->setDataUsingMethod('is_in_stock', $data['stock_status']);
        }

        return parent::_afterLoad($object);
    }
}
