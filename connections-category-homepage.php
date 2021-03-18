<?php
/**
 * An extension for the Connections Business Directory which add a field which can be used to define the directory homepage of a category.
 *
 * @package   Connections Business Directory Category Homepage
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      http://connections-pro.com
 * @copyright 2017 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Business Directory Category Homepage
 * Plugin URI:        http://connections-pro.com
 * Description:       An extension for the Connections Business Directory which add a field which can be used to define the directory homepage of a category.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections-category-homepage
 * Domain Path:       /languages
 */

if ( ! class_exists( 'Connections_Category_Homepage' ) ) {

	final class Connections_Category_Homepage {

		const VERSION = '1.0';

		/**
		 * @var string The absolute path this this file.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $basename = '';

		/**
		 * Stores the instance of this class.
		 *
		 * @var $instance Connections_Category_Homepage
		 *
		 * @access private
		 * @static
		 * @since  1.0
		 */
		private static $instance;

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @access public
		 * @since  1.0
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * The main plugin instance.
		 *
		 * @access  private
		 * @static
		 * @since   1.0
		 * @return object self
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Connections_Category_Homepage ) ) {

				self::$file       = __FILE__;
				self::$url        = plugin_dir_url( self::$file );
				self::$path       = plugin_dir_path( self::$file );
				self::$basename   = plugin_basename( self::$file );

				self::$instance = new Connections_Category_Homepage;

				// This should run on the `plugins_loaded` action hook. Since the extension loads on the
				// `plugins_loaded action hook, call immediately.
				self::loadTextdomain();

				if ( is_admin() ){

					include dirname( __FILE__ ) . '/includes/class.term-homepage.php';
					new cnTerm_Homepage( __FILE__ );
				}

				add_filter( 'cn_term_link', array( __CLASS__, 'permalink' ), 10, 4 );
			}

			return self::$instance;
		}

		/**
		 * Load the plugin translation.
		 *
		 * Credit: Adapted from Ninja Forms / Easy Digital Downloads.
		 *
		 * @access private
		 * @since  1.0
		 */
		public static function loadTextdomain() {

			// Plugin textdomain. This should match the one set in the plugin header.
			$domain = 'connections-category-homepage';

			// Set filter for plugin's languages directory
			$languagesDirectory = apply_filters( "cn_{$domain}_languages_directory", CN_DIR_NAME . '/languages/' );

			// Traditional WordPress plugin locale filter
			$locale   = apply_filters( 'plugin_locale', get_locale(), $domain );
			$fileName = sprintf( '%1$s-%2$s.mo', $domain, $locale );

			// Setup paths to current locale file
			$local  = $languagesDirectory . $fileName;
			$global = WP_LANG_DIR . "/{$domain}/" . $fileName;

			if ( file_exists( $global ) ) {

				// Look in global `../wp-content/languages/{$domain}/` folder.
				load_textdomain( $domain, $global );

			} elseif ( file_exists( $local ) ) {

				// Look in local `../wp-content/plugins/{plugin-directory}/languages/` folder.
				load_textdomain( $domain, $local );

			} else {

				// Load the default language files
				load_plugin_textdomain( $domain, FALSE, $languagesDirectory );
			}
		}

		/**
		 * Callback for the `cn_term_link` filter.
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @return string
		 */
		public static function permalink( $link, $term, $taxonomy, $atts ) {

			//if ( ! $slug = cnQuery::getVar( 'cn-entry-slug' ) ) {
			//
			//	return $link;
			//}

			if ( 'category' !== $taxonomy ) {

				return $link;
			}

			$slugs     = array();
			$ancestors = cnTerm::getAncestors( $term->term_id, $taxonomy );
			$homepage  = ( $meta = self::getHomepage( $term->term_id ) ) ? $meta : $atts['home_id'];

			if ( empty( $meta ) && $ancestors ) {

				$ancestors = array_reverse( $ancestors );

				foreach ( (array) $ancestors as $ancestor ) {

					$ancestor_term = cnTerm::get( $ancestor, $taxonomy );
					$slugs[]       = $ancestor_term->slug;

					$homepage = ( $ameta = self::getHomepage( $ancestor ) ) ? $ameta : $homepage;
				}
			}

			$slugs   = array_reverse( $slugs );
			$slugs[] = $term->slug;

			$link = cnURL::permalink(
				array(
					'type'       => 'category-taxonomy-term',
					'slug'       => implode( '/', $slugs ),
					'title'      => $term->name,
					'text'       => $term->name,
					'data'       => 'url',
					'force_home' => $atts['force_home'],
					'home_id'    => $homepage,
					'return'     => TRUE,
				)
			);

			return $link;
		}

		/**
		 * Return the `meta_key` of a term
		 *
		 * @access public
		 * @since  1.0
		 *
		 * @param int $term_id
		 *
		 * @return array|bool|string
		 */
		public static function getHomepage( $term_id = 0 ) {

			return cnMeta::get( 'term', $term_id, 'homepage', TRUE );
		}
	}

	/**
	 * Start up the extension.
	 *
	 * @access                public
	 * @since                 1.0
	 * @return mixed (object)|(bool)
	 */
	function Connections_Category_Homepage() {

		if ( class_exists( 'connectionsLoad' ) ) {

			return Connections_Category_Homepage::instance();

		} else {

			add_action(
				'admin_notices',
				create_function(
					'',
					'echo \'<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use Connections Category Homepage.</p></div>\';'
				)
			);

			return FALSE;
		}
	}

	/**
	 * We'll load the extension on `plugins_loaded` so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Category_Homepage' );
}
