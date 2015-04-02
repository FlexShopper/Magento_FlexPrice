<?php
/**
 * Magento version/edition helper for determining the Magento BASE version (regardless of edition) and adds functions to check if Enterprise, Professional or Community are being run.
 * Adds isMageCommunity(), isMageProfessional() and isMageEnterprise()
 */
class FlexShopper_Payment_Helper_Version extends Mage_Core_Helper_Abstract {

    /**
     * True if the version of Magento currently being rune is Enterprise Edition
     */
    public function isMageEnterprise() {
        return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );
    }


    /**
     * True if the version of Magento currently being rune is Enterprise Edition
     */
    public function isMageProfessional() {
        return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );
    }



    /**
     * True if the version of Magento currently being rune is Enterprise Edition
     */
    public function isMageCommunity() {
        return !$this->isMageEnterprise() && !$this->isMageProfessional();
    }


    /**
     * attempt to convert an Enterprise, Professional, Community magento version number to its compatable Community version
     *
     * @param string $task fix problems where direct version numbers cant be changed to a community release without knowing the intent of the task
     */
    public function convertVersionToCommunityVersion($version, $task = null) {

        /* Enterprise -
         * 1.9 | 1.8 | 1.5
         */
        if ($this->isMageEnterprise()) {
            if (version_compare ( $version, '1.11.0', '>=' ))
                return '1.6.0';
            if (version_compare ( $version, '1.9.1', '>=' ))
                return '1.5.0';
            if (version_compare ( $version, '1.9.0', '>=' ))
                return '1.4.2';
            if (version_compare ( $version, '1.8.0', '>=' ))
                return '1.3.1';
            return '1.3.1';
        }

        /* Professional -
         * If Entprise_Enterprise module is installed but it didn't pass Enterprise_Enterprise tests
         * then the installation must be Magento Pro edition.
         * 1.7 | 1.8
         */
        if ($this->isMageProfessional()) {
            if (version_compare ( $version, '1.8.0', '>=' ))
                return '1.4.1';
            if (version_compare ( $version, '1.7.0', '>=' ))
                return '1.3.1';
            return '1.3.1';
        }

        /* Community -
         * 1.5rc2 - December 29, 2010
         * 1.4.2 - December 8, 2010
         * 1.4.1 - June 10, 2010
         * 1.3.3.0 - (April 23, 2010) *** does this release work like to 1.4.0.1?
         * 1.4.0.1 - (February 19, 2010)
         * 1.4.0.0 - (February 12, 2010)
         * 1.3.0 - March 30, 2009
         * 1.2.1.1 - February 23, 2009
         * 1.1 - July 24, 2008
         * 0.6.1316 - October 18, 2007
         */
        return $version;
    }


    /**
     * Returns true if the base Magento version is what has been specified
     * @param string $version
     * @param unknown_type $task
     * @return boolean
     */
    public function isBaseMageVersion($version, $task = null) {
        // convert Magento Enterprise, Professional, Community to a base version
        $mage_base_version = $this->convertVersionToCommunityVersion ( Mage::getVersion (), $task );

        if (version_compare ( $mage_base_version, $version, '=' )) {
            return true;
        }
        return false;
    }
}
