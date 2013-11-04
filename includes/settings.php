<?php

/**
 * Class VirtualPostsLoader
 * Settings with keys for virtualposts, if not in cache then save it to options
 * Dependent of php_fast_cache!
 */
class VirtualPostsSettings {

	const cache_key  = 'virtualposts_settings';
	const cache_time = 3600;

	static function get( $key ) {
		$options = VirtualPostsSettings::cache();
		if ( ! $options || ! array_key_exists( $key, $options ) || ! is_array( $options[$key] ) ) return array();
		return $options[$key];
	}

	static function update( $key, $value ) {
		if ( ! is_array( $value ) ) return;
		$options = unserialize( get_option( VirtualPostsSettings::cache_key ) );
		if ( ! is_array( $options ) ) $options = array();
		$options[$key] = $value;
		update_option( VirtualPostsSettings::cache_key, serialize( $options ) );
		phpFastCache::delete( VirtualPostsSettings::cache_key );
	}

	protected static function cache( $reset = null ) {

		if ( $reset ) {
			phpFastCache::delete( VirtualPostsSettings::cache_key );
			apc_clear_cache();
		}

		phpFastCache::$storage = VirtualPostsSettings::get_general_cache_type();
		$options               = phpFastCache::get( VirtualPostsSettings::cache_key );

		if ( $options == null ) {
			$options = get_option( VirtualPostsSettings::cache_key );
			$options = unserialize( $options );
			if ( ! $options ) $options = array();
			phpFastCache::set( VirtualPostsSettings::cache_key, $options, VirtualPostsSettings::cache_time );
		}

		return $options;

	}

	protected static function get_general_cache_type() {

		$result = 'options';

		phpFastCache::$storage = 'auto';
		$options               = phpFastCache::get( VirtualPostsSettings::cache_key );
		if ( $options && is_array( $options ) ) {
			if ( $options['general'] && is_array( $options['general'] ) ) {
				if ( array_key_exists( 'cache', $options['general'] ) ) return $options['general']['cache'];
			}
		}

		if ( $result == 'options' ) {
			$result  = 'auto';
			$options = get_option( VirtualPostsSettings::cache_key );
			$options = unserialize( $options );
			if ( is_array( $options ) ) {
				if ( is_array( $options['general'] ) ) {
					if ( array_key_exists( 'cache', $options['general'] ) ) return $options['general']['cache'];
				}
			}
		}

		return $result;

	}

	static function delete_options() {

		delete_option( VirtualPostsSettings::cache_key );
		phpFastCache::delete( 'virtualposts' );

	}

}
