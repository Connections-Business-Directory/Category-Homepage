<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'cnTerm_Homepage' ) ) :

/**
 * @since 1.0
 */
final class cnTerm_Homepage extends cnTerm_Meta_UI {

	const VERSION = '1.0';

	/**
	 * Stores the instance of this class.
	 *
	 * @var int
	 *
	 * @access private
	 * @since  1.0
	 * @static
	 */
	private static $instance;

	/**
	 * @since  1.0
	 *
	 * @var string Meta data key
	 */
	public $meta_key = 'homepage';

	/**
	 * A dummy constructor to prevent the class from being loaded more than once.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param string $file
	 */
	public function __construct( $file = '' ) {

		// Setup the labels
		$this->labels = array(
			'singular'    => esc_html__( 'Directory Homepage', 'connections-category-homepage' ),
			//'plural'      => esc_html__( 'Images', 'connections-category-homepage' ),
			'description' => esc_html__(
				'Choose the page to be used as the directory homepage for this category. The category descendants will inherit the selected homepage. The selected page must include the [connections] shortcode. This will override the directory homepage set on the Settings page.',
				'connections-category-homepage'
			),
		);

		parent::__construct( $file );
	}

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @access public
	 * @since 1.0
	 * @static
	 *
	 * @param string $file
	 *
	 * @return cnTerm_Homepage
	 */
	public static function instance( $file = '' ) {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof cnTerm_Homepage ) ) {

			self::$instance = new self( $file );
		}

		return self::$instance;
	}

	/**
	 * Add help tab for `image` column.
	 *
	 * @access public
	 * @since  1.0
	 */
	public function help_tabs() {

		get_current_screen()->add_help_tab(
			array(
				'id'      => 'cn_category_homepage_help_tab',
				'title'   => esc_html__( 'Category Homepage', 'connections-category-homepage' ),
				'content' => '<p>' . esc_html__(
						'Help text here.',
						'connections-category-homepage' ) . '</p>',
			)
		);
	}

	/**
	 * @access public
	 * @since 1.0
	 */
	public function admin_head() {}

	/**
	 * Output the "term-homepage" form field when adding a new term.
	 *
	 * @since 1.0
	 *
	 * @param object $term
	 */
	public function formField( $term ) {

		$term_id = ! empty( $term->term_id ) ? $term->term_id : 0;

		// Remove image URL
		//$remove_url = add_query_arg(
		//	array(
		//		'action'   => 'remove-wp-term-images',
		//		'term_id'  => $term_id,
		//		'_wpnonce' => FALSE,
		//	)
		//);

		// Get the meta value.
		$value  = $this->get( $term_id );
		//$hidden = empty( $value ) ? ' style="display: none;"' : '';

		?>
		<div class="cn-wp-page-select">
			<style type="text/css" scoped>
				select.cn-wp-page-select {
					width: 100%;
				}
			</style>
			<?php
			wp_dropdown_pages(
				array(
					'name'                  => 'term-homepage',
					'class'                 => 'cn-wp-page-select',
					'echo'                  => 1,
					'show_option_none'      => __( 'Global Directory Homepage', 'connections-entry-homepage' ),
					'option_none_value'     => '0',
					'show_option_no_change' => '',
					'selected'              => $value,
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Add the "meta_key" column to taxonomy terms list-tables.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function columnHeader( $columns = array() ) {

		$columns[ $this->meta_key ] = __( 'Homepage', 'connections-entry-homepage' );

		return $columns;
	}

	/**
	 * Output the value for the custom column
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param cnTerm_Object $term
	 * @param string        $column_name
	 * @param int           $term_id
	 *
	 * @return mixed
	 */
	public function columnValue( $term, $column_name, $term_id ) {

		// Bail if no taxonomy passed or not on the `meta_key` column
		if ( $this->meta_key !== $column_name ) {

			return $term;
		}

		// Get the metadata
		$meta = $this->get( $term_id );

		$ancestors = cnTerm::getAncestors( $term->term_id, $term->taxonomy );

		if ( empty( $meta ) && $ancestors ) {

			$ancestors = array_reverse( $ancestors );

			foreach ( (array) $ancestors as $ancestor ) {

				$meta = ( $ameta = $this->get( $ancestor ) ) ? $ameta : $meta;
			}
		}

		// Output HTML element if not empty
		if ( ! empty( $meta ) ) {

			$html = $this->renderColumnValue( $meta );

		} else {

			$html = $this->renderColumnValue();
		}

		echo $html;

		return $term;
	}

	/**
	 * Return the formatted output for the column row.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function renderColumnValue( $value = '' ) {

		$id = $value ? $value : cnSettingsAPI::get( 'connections', 'connections_home_page', 'page_id' );

		return get_the_title( $id );
	}
}
endif;
