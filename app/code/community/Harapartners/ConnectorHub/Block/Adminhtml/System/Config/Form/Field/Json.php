<?php
/*
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license [^]
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 *
 */

class Harapartners_ConnectorHub_Block_Adminhtml_System_Config_Form_Field_Json extends Mage_Adminhtml_Block_System_Config_Form_Field {
	
    protected function _toHtml() {
    	$element = $this->getData('element');
    	$elementFieldConfig = $element->getFieldConfig();
        $elementJsonConfig = array(
        		'structure'		=> (string)$elementFieldConfig->frontend_json_structure,
        		'key_label'		=> (string)$elementFieldConfig->frontend_json_key_label,
        		'value_label'	=> (string)$elementFieldConfig->frontend_json_value_label,
        );
    	
    	$elementData = $this->_prepareElementData($element, $elementJsonConfig);
    	$elementJsonConfig = json_encode($elementJsonConfig);

		$htmlContent = <<< HTML_CONTENT
<input type="hidden" id="{$element->getHtmlId()}" name="{$element->getName()}" value="{$element->getEscapedValue()}" {$this->serialize($element->getHtmlAttributes())}/>
<div id="{$element->getHtmlId()}_json_config_widget" class="json_config_widget_container"></div>
<script type="text/javascript">
	var {$element->getHtmlId()} = new JsonConfigWidget();
    {$element->getHtmlId()}.init("{$element->getHtmlId()}", $elementData, $elementJsonConfig);
</script>
HTML_CONTENT;
    	return $htmlContent;
    }
    
    protected function _prepareElementData($element){
    	$elementFieldConfig = $element->getFieldConfig();
    	$elementJsonConfig = array(
        		'structure'		=> (string)$elementFieldConfig->frontend_json_structure,
        		'key_label'		=> (string)$elementFieldConfig->frontend_json_key_label,
        		'value_label'	=> (string)$elementFieldConfig->frontend_json_value_label,
        );
    	$elementData = (string)$element->getValue();
    	
    	//Try to decode to validate the data, if not validate, use empty placeholders
    	$jsonValidateData = json_decode(trim($element->getValue()), true);
        if(!$jsonValidateData){
        	if(isset($elementJsonConfig['structure']) && $elementJsonConfig['structure'] == 'list'){
        		$elementData = "[]";
        	}else{
        		$elementData = "{}";
        	}
        }
        
        return $elementData;
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element){
        $this->addData(array(
            'element' => $element
        ));
        return $this->_toHtml();
    }
    
}
