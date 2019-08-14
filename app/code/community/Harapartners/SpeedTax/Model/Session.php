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

class Harapartners_SpeedTax_Model_Session extends Mage_Core_Model_Session_Abstract {

	const REQUEST_CACHE_TTL = 600;
	
    public function __construct() {
        $this->init('speedtax');
    }
    
    public function loadCachedResponse($sptxInvoiceCacheKey){
    	$cacheStorage = $this->getData('cache_storage');
    	if(!isset($cacheStorage[$sptxInvoiceCacheKey])){
    		return false;
    	}
    	$cacheEntry = $cacheStorage[$sptxInvoiceCacheKey];
    	if($this->_isCacheEntryValid($cacheEntry)){
    		return json_decode($cacheEntry['response_json']);
    	}
    	return false;
    }
    
    public function saveCachedResponse($sptxInvoiceCacheKey, $response){
    	$cacheStorage = $this->getData('cache_storage');
    	if(!$cacheStorage){
    		$cacheStorage = array();
    	}
    	$cacheStorage[$sptxInvoiceCacheKey] = array(
    			'timestamp' => time(),
    			'response_json' => json_encode($response)
    	);
    	$cacheStorage = $this->_clearExpiredEnties($cacheStorage);
    	$this->setData('cache_storage', $cacheStorage);
    	return true;
    }
    
    protected function _clearExpiredEnties($cacheStorage){
    	foreach($cacheStorage as $sptxInvoiceCacheKey => $cacheEntry){
	    	if(!$this->_isCacheEntryValid($cacheEntry)){
	    		unset($cacheStorage[$sptxInvoiceCacheKey]);
	    	}
    	}
    	return $cacheStorage;
    }
    
    protected function _isCacheEntryValid($cacheEntry){
    	return isset($cacheEntry['timestamp']) 
    			&& $cacheEntry['timestamp'] + self::REQUEST_CACHE_TTL > time()
    			&& isset($cacheEntry['response_json']);
    }

}