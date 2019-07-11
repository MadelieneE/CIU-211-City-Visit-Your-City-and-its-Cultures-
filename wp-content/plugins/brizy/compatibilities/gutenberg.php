<?php

class Brizy_Compatibilities_Gutenberg {

	public function __construct() {
		add_filter( 'the_content', array( $this, 'filter_the_content' ), 5 );
		add_action( 'admin_print_scripts-edit.php', array( $this, 'add_edit_button_to_gutenberg' ), 12 );
		add_action( 'admin_init', array( $this, 'action_disable_gutenberg' ) );
		add_action( 'admin_footer', array( $this, 'print_admin_footer_tpls' ) );
		add_action( 'rest_api_init', array( $this, 'create_feature_image_focal_point_field' ) );

	}


	public function filter_the_content( $content ) {
		remove_filter( 'the_content', 'gutenberg_wpautop', 6 );

		return $content;
	}

	public function add_edit_button_to_gutenberg() {
		global $typenow;

		$new_post_url = add_query_arg( array(
			'action'    => 'brizy_new_post',
			'post_type' => $typenow,
		), set_url_scheme( admin_url( 'edit.php' ) ) );

		?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var dropdown = document.querySelector('#split-page-title-action .dropdown');

                if (!dropdown) {
                    return;
                }

                var url = '<?php echo esc_attr( $new_post_url ); ?>';

                dropdown.insertAdjacentHTML('afterbegin', '<a href="' + url + '">Brizy</a>');
            });
        </script>
		<?php
	}

	public function action_disable_gutenberg() {

		global $pagenow;

		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) || ! isset( $_GET['post'] ) ) {
			return;
		}

		if ( ! in_array( get_post_type( $_GET['post'] ), Brizy_Editor::get()->supported_post_types() ) ) {
			return;
		}

		try {
			if ( Brizy_Editor_Post::get( $_GET['post'] )->uses_editor() ) {
				add_filter( 'gutenberg_can_edit_post_type', '__return_false' );
			}
		} catch ( Exception $e ) {
			return;
		}
	}

	public function print_admin_footer_tpls() {

		global $pagenow;

		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
			return;
		}

		if ( ! in_array( get_post_type(), Brizy_Editor::get()->supported_post_types() ) ) {
			return;
		}

		$continueUrl = add_query_arg(
			array( Brizy_Editor_Constants::EDIT_KEY => '' ),
			get_permalink( get_the_ID() )
		);

		$log_dir = BRIZY_PLUGIN_URL . '/admin/static/img/';

		try {
			$post = Brizy_Editor_Post::get( get_the_ID() );

			if ( $post->uses_editor() ) {
				$edit_url = esc_url( admin_url( 'admin-post.php?action=_brizy_admin_editor_disable&post=' . get_the_ID() ) );
				?>
                <script id="brizy-gutenberg-btn-switch-mode" type="text/html">
                    <div class="brizy-buttons" style="margin-top:15px;">
                        <a class="brizy-button brizy-button--primary enable-brizy-editor" type="button"
                           href="<?php echo $edit_url ?>">
                            <img src="<?php echo plugins_url( '../admin/static/img/arrow.png', __FILE__ ) ?>"
                             class="brizy-button--arrow" /> <?php echo __( 'Back to WordPress Editor', 'brizy' ) ?>
                        </a>
                    </div>
                </script>
                <script id="brizy-gutenberg-btn-middle" type="text/html">
                    <div class="brizy-buttons-gutenberg">
                        <a class="brizy-button brizy-button--primary " type="button" href="<?php echo $continueUrl; ?>"
                           style="padding:5px 27px 5px;"><?php echo __( 'Continue to edit with ', 'brizy' ); ?><img src="<?php echo __bt( 'brizy-logo', plugins_url( '../admin/static/img/brizy.png', __FILE__ ) ); ?>" class="brizy-logo"/><?php echo __bt( 'brizy', 'Brizy' ); ?>
                        </a>
                    </div>
                </script>

				<?php
			} else {
				$edit_url = esc_url( admin_url( 'admin-post.php?action=_brizy_admin_editor_enable&post=' . get_the_ID() ) );
				?>
                <script id="brizy-gutenberg-btn-switch-mode" type="text/html">
                        <div class="brizy-buttons" >
                            <a class="brizy-button brizy-button--primary enable-brizy-editor" type="button" href="<?php echo $edit_url;?>"><?php echo esc_html__( 'Edit with', 'brizy' ) ?>
                                <img width="16" src="<?php echo __bt( 'brizy-logo', plugins_url( '../admin/static/img/brizy.png', __FILE__ ) ); ?>"
                                     srcset="<?php echo __bt( 'brizy-logo', plugins_url( '../admin/static/img/brizy.png', __FILE__ ) ) ?> 1x, <?php echo __bt( 'brizy-logo-2x', plugins_url( '../admin/static/img/brizy-2x.png', __FILE__ ) );?> 2x"
                                     class="brizy-logo"><?php echo __bt( 'brizy', 'Brizy' ); ?>
                             </a>
                        </div>
                    </script>
                <?php
			}
		} catch ( Exception $e ) {

		}
	}


	public function create_feature_image_focal_point_field() {
		register_rest_field( 'page', 'brizy_attachment_focal_point', array(
				'get_callback'    => function ( $post, $field_name, $request ) {
					return get_post_meta( $post['id'], $field_name, true );
				},
				'update_callback' => function ( $meta_value, $post ) {
					update_post_meta( $post->ID, 'brizy_attachment_focal_point', $meta_value );
				}
			)
		);

	}
}