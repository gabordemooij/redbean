<?php

 
abstract class RedBean_ABeanFormatter implements RedBean_IBeanFormatter {


	/**
	 *
	 * @param string $type type
	 */
	public function formatBeanTable( $type ){
		return $type;
	}

	/**
	 *
	 * @param string $type type
	 */
	public function formatBeanID( $type ){
		return 'id';
	}

	/**
	 * @abstract
	 * @param  $type
	 * @return void
	 */
	public function getAlias( $type ) {
		return $type;
	}

}
