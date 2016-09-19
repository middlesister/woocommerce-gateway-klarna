<?php
/**
 * Klarna PClasses for KPM
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WC_Gateway_Klarna_PClasses {

	protected $pclasses;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $klarna = false, $type = false, $country = false ) {

		$this->klarna  = $klarna;
		$this->country = $country;
		$this->type    = $type;
	}

	/**
	 * Retrieves PClasses for country.
	 *
	 * @since  1.0.0
	 * @return array $pclasses Key value array of countries and their PClasses
	 */
	function get_pclasses_for_country_and_type() {
		$pclasses_country_all  = $this->pclasses;
		$pclasses_country_type = array();
		unset( $pclasses_country_type );

		if ( $pclasses_country_all ) {
			foreach( $pclasses_country_all as $eid => $pclasses_country ) {
				foreach ( $pclasses_country as $pclass ) {
					if ( in_array( $pclass->type, $this->type ) ) { // Passed from parent file
						$pclasses_country_type[] = $pclass;
					}
				}
			}
		}

		if ( ! empty( $pclasses_country_type ) ) {
			return $pclasses_country_type;
		}
	}

	/**
	 * Retrieve the PClasses from Klarna
	 *
	 * @since 1.0.0
	 */
	function fetch_pclasses() {
		$klarna = $this->klarna;
		$klarna_pclasses = get_transient( 'klarna_pclasses_' . $klarna->getCountryCode() );

		if ( is_array( $klarna_pclasses ) ) {
			return $klarna_pclasses;
		} else if ( $fetched_pclasses = $klarna->getPClasses( $klarna->getCountryCode() ) ) { // fetch PClasses from Klarna
			// Format them
			foreach ( $fetched_pclasses as $fetched_pclass ) {
				$this->add_pclass( $fetched_pclass );
			}

			// Store transient
			try {
				$output = array();
				foreach ($this->pclasses as $eid => $pclasses) {
					foreach ($pclasses as $pclass) {
						if (!isset($output[$eid])) {
							$output[$eid] = array();
						}
						$pclass_array = $pclass->toArray();
						// Clean up description
						foreach ( $pclass_array as $pclass_key => $pclass_value ) {
							$pclass_array[ $pclass_key ] = mb_convert_encoding( $pclass_value, 'UTF-8' );
						}

						$output[$eid][] = $pclass_array;
					}
				}

				// $json_output = json_encode( $output );

				if ( count( $this->pclasses ) > 0 ) {
					set_transient( 'klarna_pclasses_' . $klarna->getCountryCode(), $output, 12 * HOUR_IN_SECONDS );
					return $output;
				} else {
					delete_transient( 'klarna_pclasses_' . $klarna->getCountryCode() );
				}

				return $output;
			} catch(Exception $e) {
				throw new KlarnaException( $e->getMessage() );
			}
		} else {
			return false;
		}
	}

	/**
	 * Adds a PClass to the storage.
	 *
	 * @param $pclass Klarna\XMLRPC\PClass object.
	 *
	 * @throws Klarna\XMLRPC\Exception\KlarnaException
	 * @return void
	 */
	public function add_pclass( $pclass ) {
		if ( ! $pclass instanceof Klarna\XMLRPC\PClass ) {
			throw new Klarna\XMLRPC\Exception\KlarnaException( 'pclass', 'KlarnaPClass' );
		}

		if ( ! isset( $this->pclasses ) || ! is_array( $this->pclasses ) ) {
			$this->pclasses = array();
		}

		if ( $pclass->getDescription() === null || $pclass->getType() === null ) {
			// Something went wrong, do not save these!
			return;
		}

		if ( ! isset($this->pclasses[ $pclass->getEid() ] ) ) {
			$this->pclasses[$pclass->getEid()] = array();
		}

		$this->pclasses[$pclass->getEid()][$pclass->getId()] = $pclass;
	}

}