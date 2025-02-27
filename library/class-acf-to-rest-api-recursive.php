<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ACF_To_REST_API_Recursive' ) ) {
	class ACF_To_REST_API_Recursive {

		protected static $recursedPosts = [];
		public static $postRecursionDepth = 5;

		public static function init() {
			self::hooks();
		}

		private static function hooks() {
			$types = self::get_types();

			self::append_fields( $types );
		}

		protected static function get_types() {
			$types = array(
				'options'     => 'options',
				'comments'    => 'comments',
				'users'       => 'users'
			);

			$types += (array) get_post_types( array( 'show_in_rest' => true ) );
			$types += (array) get_taxonomies( array( 'show_in_rest' => true ) );

			return apply_filters( 'acf/rest_api/recursive/types', $types );
		}

		private static function append_fields( $objects ) {
			if ( ! empty( $objects ) ) {
				foreach ( $objects as $obj ) {
					add_filter( 'acf/rest_api/' . $obj . '/get_fields', array( __CLASS__, 'get_fields' ) );
				}
			}
		}

		public static function get_fields( $data ) {
			if ( ! empty( $data ) ) {
				array_walk_recursive( $data, array( __CLASS__, 'get_fields_recursive' ) );
			}

			return $data;
		}

		public static function get_fields_recursive( $item ) {
			if ( is_object( $item ) ) {
				if (get_class($item) === 'WP_Post') {
					static::$recursedPosts[$item->ID] = static::$recursedPosts[$item->ID] ?? 0;
					static::$recursedPosts[$item->ID]++;
				}

				$item->acf = array();
				$depth = $item->depth ?? 0;
				$fields = get_fields( $item );

				if ( $fields && static::$recursedPosts[$item->ID] < static::$postRecursionDepth ) {
					$item->acf = $fields;
					array_walk_recursive( $item->acf, array( __CLASS__, 'get_fields_recursive' ) );
				}
			}
		}
	}
}