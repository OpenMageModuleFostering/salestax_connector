<?php 
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User Software Agreement (EULA).
 * It is also available through the world-wide-web at this URL:
 * http://www.harapartners.com/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to eula@harapartners.com so we can send you a copy immediately.
 * 
 */

class Harapartners_ConnectorHub_Model_Observer {
	
	// ============================== Observers ============================== //
	public function adminhtmlOnlyCoreBlockAbstractToHtmlAfter(Varien_Event_Observer $observer){
        $block = $observer->getEvent()->getBlock();
        $transportObject = $observer->getEvent()->getTransport();
        if($block instanceof Mage_Page_Block_Html_Head){
        	$html = $transportObject->getHtml();
        	$html .= $this->_getJsonConfigWidgetCss();
			$html .= $this->_getJsonConfigWidgetJs();
			$transportObject->setHtml($html);
        }
        return;
	}
	
	protected function _getJsonConfigWidgetCss() {
		$jsonConfigWidgetCss = <<< JSON_CONFIG_WIDGET_CSS
<style>
.json_config_widget_container thead{
	background-color:#DFDFDF;
}
.json_config_widget_container th, .json_config_widget_container td{
	padding:2px;
}
.json_config_widget_container td input{
	text-align:right
}
.json_config_widget_container button{
	margin:5px;
}
.json_config_widget_message_container .success{
	font-weight: bold;
	color: #3D6611
}
</style>
JSON_CONFIG_WIDGET_CSS;
		return $jsonConfigWidgetCss;
	}
	
	protected function _getJsonConfigWidgetJs() {
		$jsonConfigWidgetJs = <<< JSON_CONFIG_WIDGET_JS
<script type="text/javascript">
function JsonConfigWidget() {

	var _const;
	var _config;
	var _elementId;
	var _elementData; //JSON for maps, ARRAY for lists
	var _renderMode;
	
	// ==================== Controllers ==================== //
	this.init = function (elementId, elementValue, elementConfig) {
		this._const = {
				contentMode:	0,
				editMode:		1,
				mapStructure:	'map',
				listStructure:	'list'
		}
		this._elementId = elementId;
		this._elementData = elementValue;
		this._config = elementConfig;
		this._renderMode = this._const.contentMode; //Default is content mode
		
		var tableHead = "";
		if(this._config.structure == this._const.mapStructure){
			tableHead = "<tr><th>" + this._config.key_label + "</th><th>" + this._config.value_label + "</th></tr>";
		}else if(this._config.structure == this._const.listStructure){
			tableHead = "<tr><th>" + this._config.value_label + "</th></tr>";
		}
		var contentTemplate = '<div class="json_config_widget_message_container"></div>'
					+ '<table border="1" style="border-collapse:collapse; text-align:right">' 
					+ '<thead class="json_config_widget_thead">' + tableHead + '</thead>'
					+ '<tbody class="json_config_widget_tbody"></tbody>'
					+ '</table>'
					+ '<button type="button" class="scalable add json_config_widget_button_add" style="display: none"><span>Add Entry</span></button>'
					+ '<button type="button" class="scalable save json_config_widget_button_confirm"><span>Edit</span></button>';
					
		$(this._elementId + "_json_config_widget").update(contentTemplate);
		$$("div#" + this._elementId + "_json_config_widget button.json_config_widget_button_add").first().observe('click', this.addInputObserver.bind(this));
		$$("div#" + this._elementId + "_json_config_widget button.json_config_widget_button_confirm").first().observe('click', this.toggleViewObserver.bind(this));
		this.renderContentView();
	}

	// ==================== Models ==================== //
	this.processEditData = function () {
		var tempDataDump = []; 
		$$("div#" + this._elementId + "_json_config_widget tbody.json_config_widget_tbody input").each(function (item){
			tempDataDump[tempDataDump.length] = item.value;
		});
		if(this._config.structure == this._const.mapStructure){
			if(tempDataDump.length%2 != 0){
				this.showMessage('error', 'Invalid inputs.');
				return false;
			}
			this._elementData = {};
			for (var tempIndex=0; tempIndex<tempDataDump.length; tempIndex += 2){
				cleanedJsonKey = tempDataDump[tempIndex].trim();
				cleanedJsonValue = tempDataDump[tempIndex + 1].trim();
				if(cleanedJsonKey.length == 0 && cleanedJsonValue.length == 0){
					//Both empty rows are ignored
					continue;
				}
				if(cleanedJsonKey.length == 0 || cleanedJsonValue.length == 0){
					//Otherwise, either empty will be an error
					this.showMessage('error', 'Missing input keys or values.');
					return false;
				}
				this._elementData[cleanedJsonKey] = cleanedJsonValue;
			}
		}else if(this._config.structure == this._const.listStructure){
			this._elementData = [];
			for (var tempIndex=0; tempIndex<tempDataDump.length; tempIndex ++){
				cleanedJsonValue = tempDataDump[tempIndex].trim();
				if(cleanedJsonValue.length == 0){
					continue;
				}
				this._elementData[tempIndex] = cleanedJsonValue;
			}
		}
		
		this.showMessage('success', 'Content udpated. Please make sure to save config.');
		return true;
	}
	
	// ==================== Views ==================== //
	this.showMessage = function (messageClass, messageContent) {
		$$("div#" + this._elementId + "_json_config_widget div.json_config_widget_message_container").first().update('<span class="' + messageClass + '">' + messageContent + '</span>');
	}
	
	this.renderContentView = function () {
		var tableContent = "";
		if(this._config.structure == this._const.mapStructure){
			for(jsonKey in this._elementData){
				if(this._elementData.hasOwnProperty(jsonKey)){
					jsonValue = this._elementData[jsonKey];
					tableContent += "<tr><td>" + jsonKey + "</td><td>" + jsonValue + "</td></tr>";
				}
			}
		}else if(this._config.structure == this._const.listStructure){
			for(var tempIndex = 0; tempIndex < this._elementData.length; tempIndex ++){
				tableContent += "<tr><td>" + this._elementData[tempIndex] + "</td></tr>";
			}
		}
		
		$$("div#" + this._elementId + "_json_config_widget tbody.json_config_widget_tbody").first().update(tableContent);
		this._renderMode = this._const.contentMode;
	};

	this.renderEditView = function () {
		var tableContent = "";
		if(this._config.structure == this._const.mapStructure){
			var dummyId = 0;
			for(jsonKey in this._elementData){
				if(this._elementData.hasOwnProperty(jsonKey)){
					jsonValue = this._elementData[jsonKey];
					tableContent += "<tr><td><input name='" + this._elementId + "_json_key_'" + dummyId + " value='" + jsonKey + "'/></td>"
							+ "<td><input name='" + this._elementId + "_json_value_'" + dummyId + "  value='" + jsonValue + "'/></td>";
					dummyId ++;
				}
			}
		}else if(this._config.structure == this._const.listStructure){
			for(var tempIndex = 0; tempIndex < this._elementData.length; tempIndex ++){
				tableContent += "<tr>"
							+ "<td><input name='" + this._elementId + "_json_value_'" + tempIndex + "  value='" + this._elementData[tempIndex] + "'/></td>";
			}
		}
		$$("div#" + this._elementId + "_json_config_widget tbody.json_config_widget_tbody").first().update(tableContent);
		
		//Automatically add input fields if the data is completely empty
		if(this.isElementDataEmpty()){
			this.addInput();
		}
		
		this._renderMode = this._const.editMode;
	};
	
	this.toggleView = function () {
		if(this._renderMode == this._const.contentMode){
			this.renderEditView();
			$$("div#" + this._elementId + "_json_config_widget button.json_config_widget_button_add").first().show();
			$$("div#" + this._elementId + "_json_config_widget button.json_config_widget_button_confirm").first().update("<span>Validate</span>");
			return true;
		}else if(this._renderMode == this._const.editMode){
			//Basic validations
			if(!this.processEditData()){
				return false;
			}
			this.renderContentView();
			$(this._elementId).writeAttribute('value', JSON.stringify(this._elementData));
			$$("div#" + this._elementId + "_json_config_widget button.json_config_widget_button_add").first().hide();
			$$("div#" + this._elementId + "_json_config_widget button.json_config_widget_button_confirm").first().update("<span>Edit</span>");
			return true;
		}
		//Unknown status, default back to content view
		this.renderContentView();
		$$("div#" + this._elementId + "_json_config_widget button.json_config_widget_button_confirm").first().update("<span>Edit</span>");
		return true;
	}
	
	this.addInput = function () {
		var tempDataDump = [];
		$$("div#" + this._elementId + "_json_config_widget tbody.json_config_widget_tbody input").each(function (item){
			tempDataDump[tempDataDump.length] = item.value;
		});
		
		var tableContent = "";
		if(this._config.structure == this._const.mapStructure){
			tableContent += "<tr><td><input name='" + this._elementId + "_json_key_'" + (tempDataDump.length + 1) + " value=''/></td>"
				+ "<td><input name='" + this._elementId + "_json_value_'" + (tempDataDump.length + 2) + "  value=''/></td>";
		}else if(this._config.structure == this._const.listStructure){
			tableContent += "<tr><td><input name='" + this._elementId + "_json_value_'" + (tempDataDump.length + 1) + "  value=''/></td>";
		}

		$$("div#" + this._elementId + "_json_config_widget tbody.json_config_widget_tbody").first().insert(tableContent);
		return true;
	}
	
	// ==================== Oberservers ==================== //
	this.toggleViewObserver = function (event) {
		this.toggleView();
	}
	
	this.addInputObserver = function (event) {
		this.addInput();
	}
	
	// ==================== Utilities ==================== //
	this.isElementDataEmpty = function () {
	    if (this._elementData == null) return true;
	    if (this._elementData.length > 0)    return false;
	    if (this._elementData.length === 0)  return true;
	    for (var jsonKey in this._elementData) {
	        if (this._elementData.hasOwnProperty(jsonKey)) return false;
	    }
	    return true;
	}
	
}
</script>
JSON_CONFIG_WIDGET_JS;
		return $jsonConfigWidgetJs;
	}
	
}