<?php

/**
 * Copyright (c) 2018-2020 Comet Licensing Ltd.
 * Please see the LICENSE file for usage information.
 * 
 * SPDX-License-Identifier: MIT
 */

namespace Comet;

class BucketUsageInfo {
	
	/**
	 * @var int[]
	 */
	public $ExistsOnServers = [];
	
	/**
	 * @var \Comet\UserOnServer[]
	 */
	public $InUseBy = [];
	
	/**
	 * Preserve unknown properties when dealing with future server versions.
	 *
	 * @see BucketUsageInfo::RemoveUnknownProperties() Remove all unknown properties
	 * @var array
	 */
	private $__unknown_properties = [];
	
	/**
	 * Replace the content of this BucketUsageInfo object from a PHP \stdClass.
	 * The data could be supplied from an API call after json_decode(...); or generated manually.
	 *
	 * @param \stdClass $sc Object data as stdClass
	 * @return void
	 */
	protected function inflateFrom(\stdClass $sc)
	{
		if (property_exists($sc, 'ExistsOnServers')) {
			$val_2 = [];
			if ($sc->ExistsOnServers !== null) {
				for($i_2 = 0; $i_2 < count($sc->ExistsOnServers); ++$i_2) {
					$val_2[] = (int)($sc->ExistsOnServers[$i_2]);
				}
			}
			$this->ExistsOnServers = $val_2;
		}
		if (property_exists($sc, 'InUseBy')) {
			$val_2 = [];
			if ($sc->InUseBy !== null) {
				for($i_2 = 0; $i_2 < count($sc->InUseBy); ++$i_2) {
					if (is_array($sc->InUseBy[$i_2]) && count($sc->InUseBy[$i_2]) === 0) {
					// Work around edge case in json_decode--json_encode stdClass conversion
						$val_2[] = \Comet\UserOnServer::createFromStdclass(new \stdClass());
					} else {
						$val_2[] = \Comet\UserOnServer::createFromStdclass($sc->InUseBy[$i_2]);
					}
				}
			}
			$this->InUseBy = $val_2;
		}
		foreach(get_object_vars($sc) as $k => $v) {
			switch($k) {
			case 'ExistsOnServers':
			case 'InUseBy':
				break;
			default:
				$this->__unknown_properties[$k] = $v;
			}
		}
	}
	
	/**
	 * Coerce a stdClass into a new strongly-typed BucketUsageInfo object.
	 *
	 * @param \stdClass $sc Object data as stdClass
	 * @return BucketUsageInfo
	 */
	public static function createFromStdclass(\stdClass $sc)
	{
		$retn = new BucketUsageInfo();
		$retn->inflateFrom($sc);
		return $retn;
	}
	
	/**
	 * Coerce a plain PHP array into a new strongly-typed BucketUsageInfo object.
	 * Because the Comet Server requires strict distinction between empty objects ({}) and arrays ([]),
	 * the result of this method may not be safe to re-submit to the Comet Server.
	 *
	 * @param array $arr Object data as PHP array
	 * @return BucketUsageInfo
	 */
	public static function createFromArray(array $arr)
	{
		$stdClass = json_decode(json_encode($arr));
		if (is_array($stdClass) && count($stdClass) === 0) {
			$stdClass = new \stdClass();
		}
		return self::createFromStdclass($stdClass);
	}
	
	/**
	 * Coerce a plain PHP array into a new strongly-typed BucketUsageInfo object.
	 * Because the Comet Server requires strict distinction between empty objects ({}) and arrays ([]),
	 * the result of this method may not be safe to re-submit to the Comet Server.
	 *
	 * @deprecated 3.0.0 Unsafe for round-trip server traversal. You should either 
	 *             (A) acknowledge this and continue by switching to createFromArray, or
	 *             (b) switch to the roundtrip-safe createFromStdclass alternative.
	 * @param array $arr Object data as PHP array
	 * @return BucketUsageInfo
	 */
	public static function createFrom(array $arr)
	{
		return self::createFromArray($arr);
	}
	
	/**
	 * Coerce a JSON string into a new strongly-typed BucketUsageInfo object.
	 *
	 * @param string $JsonString Object data as JSON string
	 * @return BucketUsageInfo
	 */
	public static function createFromJSON($JsonString)
	{
		$decodedJsonObject = json_decode($JsonString); // as stdClass
		if (\json_last_error() != \JSON_ERROR_NONE) {
			throw new \Exception("JSON decode failed: " . \json_last_error_msg());
		}
		$retn = new BucketUsageInfo();
		$retn->inflateFrom($decodedJsonObject);
		return $retn;
	}
	
	/**
	 * Convert this BucketUsageInfo object into a plain PHP array.
	 *
	 * Unknown properties may still be represented as \stdClass objects.
	 *
	 * @param bool $for_json_encode Represent empty key-value maps as \stdClass instead of plain PHP arrays
	 * @return array
	 */
	public function toArray($for_json_encode = false)
	{
		$ret = [];
		{
			$c0 = [];
			for($i0 = 0; $i0 < count($this->ExistsOnServers); ++$i0) {
				$val0 = $this->ExistsOnServers[$i0];
				$c0[] = $val0;
			}
			$ret["ExistsOnServers"] = $c0;
		}
		{
			$c0 = [];
			for($i0 = 0; $i0 < count($this->InUseBy); ++$i0) {
				if ( $this->InUseBy[$i0] === null ) {
					$val0 = $for_json_encode ? (object)[] : [];
				} else {
					$val0 = $this->InUseBy[$i0]->toArray($for_json_encode);
				}
				$c0[] = $val0;
			}
			$ret["InUseBy"] = $c0;
		}
		
		// Reinstate unknown properties from future server versions
		foreach($this->__unknown_properties as $k => $v) {
			$ret[$k] = $v;
		}
		
		return $ret;
	}
	
	/**
	 * Convert this object to a JSON string.
	 * The result is suitable to submit to the Comet Server API.
	 *
	 * @return string
	 */
	public function toJSON()
	{
		$arr = $this->toArray(true);
		if (count($arr) === 0) {
			return "{}"; // object
		} else {
			return json_encode($arr);
		}
	}
	
	/**
	 * Convert this object to a PHP \stdClass.
	 * This may be a more convenient format for working with unknown class properties.
	 *
	 * @return \stdClass
	 */
	public function toStdClass()
	{
		$arr = $this->toArray(false);
		if (count($arr) === 0) {
			return new \stdClass();
		} else {
			return json_decode(json_encode($arr));
		}
	}
	
	/**
	 * Erase any preserved object properties that are unknown to this Comet Server SDK.
	 *
	 * @return void
	 */
	public function RemoveUnknownProperties()
	{
		$this->__unknown_properties = [];
	}
	
}
