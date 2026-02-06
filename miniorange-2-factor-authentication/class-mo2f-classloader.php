<?php
/**
 * This class loads all the classes.
 *
 * @package miniorange-2-factor-authentication
 */

namespace TwoFA;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Mo2f_Classloader' ) ) {
	/**
	 * This class loads all the classes.
	 */
	class Mo2f_Classloader {

		/**
		 * Namespace value for the plugin.
		 *
		 * @var string
		 */
		private $namespace;

		/**
		 * Include path.
		 *
		 * @var string
		 */
		private $include_path;

		/**
		 * Namespace separator.
		 *
		 * @var string
		 */
		private $namespace_separator = '\\';

		/**
		 * File extention.
		 *
		 * @var string
		 */
		private $file_extension = '.php';

		/**
		 * Initilaizes values.
		 *
		 * @param string $ns namespace value.
		 * @param string $include_path directory path.
		 */
		public function __construct( $ns = null, $include_path = null ) {
			$this->namespace    = $ns;
			$this->include_path = $include_path;
		}

		/**
		 * Calls autoload classes.
		 *
		 * @return void
		 */
		public function mo2f_autoload() {
			spl_autoload_register( array( $this, 'mo2f_autoload_classes' ) );
		}

		/**
		 * Loads all the class.
		 *
		 * @param string $class_name name of the class to include.
		 */
		public function mo2f_autoload_classes( $class_name ) {
			if ( null === $this->namespace || $this->mo2f_is_same_namespace( $class_name ) ) {
				$file_name   = '';
				$namespace   = '';
				$last_ns_pos = strripos( $class_name, $this->namespace_separator );
				if ( false !== ( $last_ns_pos ) ) {
					$namespace  = strtolower( substr( $class_name, 0, $last_ns_pos ) );
					$class_name = substr( $class_name, $last_ns_pos + 1 );
					$file_name  = str_replace( $this->namespace_separator, DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
				}
				$class_file_name = strtolower( $class_name );
				$file_name      .= str_replace( '_', '-', 'class-' . $class_file_name ) . $this->file_extension;
				$file_name1      = substr_replace( $file_name, 'miniorange-2-factor-authentication', 0, 5 );
				if ( null !== $this->include_path ) {
					require $this->include_path . DIRECTORY_SEPARATOR . $file_name1;
				} else {
					require $file_name1;
				}
			}
		}

		/**
		 * Checks if a class name is a namspace.
		 *
		 * @param string $class_name name of the class.
		 */
		private function mo2f_is_same_namespace( $class_name ) {
			return substr( $class_name, 0, strlen( $this->namespace . $this->namespace_separator ) ) === $this->namespace . $this->namespace_separator;
		}
	}
}
