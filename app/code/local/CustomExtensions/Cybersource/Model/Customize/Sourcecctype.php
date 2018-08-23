<?php
// app/code/local/CustomExtensions_Cybersource_Model_Paymentmethod/Custompaymentmethod/Model/Paymentmethod.php
class CustomExtensions_Cybersource_Model_Customize_Sourcecctype extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Allowed CC types
     *
     * @var array
     */
    protected $_allowedTypes = array();

    public function toOptionArray()
    {
        /**
         * making filter by allowed cards
         */
        $allowed = $this->getAllowedTypes();
        $options = array();

        foreach (Mage::getSingleton('cybersourcesoap/config')->getCcTypes() as $code => $name) {
            if (in_array($code, $allowed) || !count($allowed)) {
                $options[] = array(
                    'value' => $code,
                    'label' => $name
                );
            }
        }
        return $options;
    }

    /**
     * Return allowed cc types for current method
     *
     * @return array
     */
    public function getAllowedTypes()
    {
        return $this->_allowedTypes;
    }

    /**
     * Setter for allowed types
     *
     * @param $values
     * @return Mage_Payment_Model_Source_Cctype
     */
    public function setAllowedTypes(array $values)
    {
        $this->_allowedTypes = $values;
        return $this;
    }
}