<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {


	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param  array    $attributes The attributes for the block.
	 * @param  string   $content    The block content, if any.
	 * @param  WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = ! empty( $attributes['className'] ) ? $attributes['className'] : '';
		ob_start();

		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php echo esc_html__( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
				<?php
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug );

					if ( null === $post_type_object ) {
						continue;
					}

					$count_obj = wp_count_posts( $post_type_slug );
					// Assumption: We only want to retrieve count for 'published' posts.
					$post_count = property_exists( $count_obj, 'publish' ) ? $count_obj->publish : 0;
					?>
					<li>
						<?php
						// translators: %1$d: post count, %2$s: post type name.
						printf( esc_html__( 'There are %1$d %2$s.', 'site-counts' ), esc_html( $post_count ), esc_html( $post_type_object->labels->name ) );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p>
				<?php
				$post_id = isset( $_GET['post_id'] ) ? filter_input( INPUT_GET, $_GET['post_id'], FILTER_SANITIZE_NUMBER_INT ) : false; //phpcs:ignore
				/* translators: %d: post ID */
				$content = false !== $post_id ? sprintf( __( 'The current post ID is %d', 'site-counts' ), $post_id ) : __( 'Invalid post ID', 'site-counts' );

				echo esc_html( $content );
				?>
			</p>
			<?php
			$query = new WP_Query(
				[
					'post_type'     => [ 'post', 'page' ],
					'post_status'   => 'any',
					'date_query'    => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'tag'           => 'foo',
					'category_name' => 'baz',
					'post__not_in'  => [ get_the_ID() ],
				]
			);

			if ( $query->have_posts() ) :
				?>
				<h2><?php echo esc_html__( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
					<?php foreach ( array_slice( $query->posts, 0, 5 ) as $post ) : ?>
						<li><?php echo wp_kses_post( $post->post_title ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
