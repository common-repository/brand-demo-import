<?php
/**
 * The plugin page view - the "settings" page of the plugin.
 *
 * @package bdi
 */

namespace BDI;

?>

<div class="bdi  wrap  about-wrap">

	<h1 class=bdi__title  dashicons-before  dashicons-upload"><?php esc_html_e( 'Brand Demo Import', 'bdi' ); ?></h1>

	<?php

	// Display warrning if PHP safe mode is enabled, since we wont be able to change the max_execution_time.
	if ( ini_get( 'safe_mode' ) ) {
		printf(
			esc_html__( '%sWarning: your server is using %sPHP safe mode%s. This means that you might experience server timeout errors.%s', 'bdi' ),
			'<div class="notice  notice-warning  is-dismissible"><p>',
			'<strong>',
			'</strong>',
			'</p></div>'
		);
	}

	// Start output buffer for displaying the plugin intro text.
	ob_start();
	?>

	<div class="bdi__intro-notice  notice  notice-warning  is-dismissible">
		<p><?php esc_html_e( 'Before you begin, make sure all the required plugins are activated.', 'bdi' ); ?></p>
	</div>

	<div class="bdi__intro-text">
		<p class="about-description">
			<?php esc_html_e( 'Importing demo data (post, pages, images, theme settings, ...) is the easiest way to setup your theme.', 'bdi' ); ?>
			<?php esc_html_e( 'It will allow you to quickly edit everything instead of creating content from scratch.', 'bdi' ); ?>
			<?php esc_html_e( 'We recommend installing sample data on a fresh installation to prevent conflicts with your current content.', 'bdi' ); ?>
		</p>

		<hr>

	</div>

	<?php
	$plugin_intro_text = ob_get_clean();

	// Display the plugin intro text (can be replaced with custom text through the filter below).
	echo wp_kses_post( apply_filters( 'bdi/plugin_intro_text', $plugin_intro_text ) );
	?>


	<?php if ( empty( $this->import_files ) ) : ?>

		<div class="notice  notice-info  is-dismissible">
			<p><?php esc_html_e( 'There are no predefined import files available in this theme. Please upload the import files manually!', 'bdi' ); ?></p>
		</div>

		<div class="bdi__file-upload-container">
			<h2><?php esc_html_e( 'Manual demo files upload', 'bdi' ); ?></h2>

			<div class="bdi__file-upload">
				<h3><label for="content-file-upload"><?php esc_html_e( 'Choose a XML file for content import:', 'bdi' ); ?></label></h3>
				<input id="bdi__content-file-upload" type="file" name="content-file-upload">
			</div>

			<div class="bdi__file-upload">
				<h3><label for="widget-file-upload"><?php esc_html_e( 'Choose a WIE or JSON file for widget import:', 'bdi' ); ?></label> <span><?php esc_html_e( '(*optional)', 'bdi' ); ?></span></h3>
				<input id="ocdi__widget-file-upload" type="file" name="widget-file-upload">
			</div>

			<div class=bdi__file-upload">
				<h3><label for="customizer-file-upload"><?php esc_html_e( 'Choose a DAT file for customizer import:', 'bdi' ); ?></label> <span><?php esc_html_e( '(*optional)', 'bdi' ); ?></span></h3>
				<input id="bdi__customizer-file-upload" type="file" name="customizer-file-upload">
			</div>
		</div>

		<p class="bdi__button-container">
			<button class="bdi__button  button  button-hero  button-primary  js-bdi-import-data"><?php esc_html_e( 'Import Demo Data', 'bdi' ); ?></button>
		</p>

	<?php elseif ( 1 === count( $this->import_files ) ) : ?>

		<div class="bdi__demo-import-notice  js-bdi-demo-import-notice"><?php
			if ( is_array( $this->import_files ) && ! empty( $this->import_files[0]['import_notice'] ) ) {
				echo wp_kses_post( $this->import_files[0]['import_notice'] );
			}
		?></div>
		<?php
		$img_src = isset( $this->import_files[0]['import_preview_image_url'] ) ? $this->import_files[0]['import_preview_image_url'] : '';
		?>
		<p>
			<a class="demo-link" href="<?php echo esc_url( $this->import_files[0]['preview_url'] ) ?>" target="_blank">
				<img src="<?php echo esc_url( $img_src ) ?>">
			</a>
		</p>
		<p class="bdi__button-container">
			<button class="bdi__button  button  button-hero  button-primary  js-bdi-import-data"><?php esc_html_e( 'Import Demo Data', 'bdi' ); ?></button>
		</p>

	<?php else : ?>

		<!-- OCDI grid layout -->
		<div class="bdi__gl  js-bdi-gl">
		<?php
			// Prepare navigation data.
			$categories = Helpers::get_all_demo_import_categories( $this->import_files );
		?>
			<?php if ( ! empty( $categories ) ) : ?>
				<div class="bdi__gl-header  js-bdi-gl-header">
					<nav class="bdi__gl-navigation">
						<ul>
							<li class="active"><a href="#all" class="bdi__gl-navigation-link  js-bdi-nav-link"><?php esc_html_e( 'All', 'bdi' ); ?></a></li>
							<?php foreach ( $categories as $key => $name ) : ?>
								<li><a href="#<?php echo esc_attr( $key ); ?>" class="bdi__gl-navigation-link  js-bdi-nav-link"><?php echo esc_html( $name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
					<div clas="bdi__gl-search">
						<input type="search" class="bdi__gl-search-input  js-bdi-gl-search" name="bdi-gl-search" value="" placeholder="<?php esc_html_e( 'Search demos...', 'bdi' ); ?>">
					</div>
				</div>
			<?php endif; ?>
			<div class="bdi__gl-item-container  wp-clearfix  js-bdi-gl-item-container">
				<?php foreach ( $this->import_files as $index => $import_file ) : ?>
					<?php
						// Prepare import item display data.
						$img_src = isset( $import_file['import_preview_image_url'] ) ? $import_file['import_preview_image_url'] : '';
						// Default to the theme screenshot, if a custom preview image is not defined.
						if ( empty( $img_src ) ) {
							$theme = wp_get_theme();
							$img_src = $theme->get_screenshot();
						}

					?>
					<div class="bdi__gl-item js-bdi-gl-item" data-categories="<?php echo esc_attr( Helpers::get_demo_import_item_categories( $import_file ) ); ?>" data-name="<?php echo esc_attr( strtolower( $import_file['import_file_name'] ) ); ?>">
						<div class="bdi__gl-item-image-container">
							<?php if ( ! empty( $img_src ) ) : ?>
								<img class="bdi__gl-item-image" src="<?php echo esc_url( $img_src ) ?>">
							<?php else : ?>
								<div class="bdi__gl-item-image  bdi__gl-item-image--no-image"><?php esc_html_e( 'No preview image.', 'bdi' ); ?></div>
							<?php endif; ?>
						</div>
						<div class="bdi__gl-item-footer">
							<h4 class="bdi__gl-item-title"><?php echo esc_html( $import_file['import_file_name'] ); ?></h4>
							<a class="preview button" href="<?php echo esc_url( $import_file['preview_url'] ) ?>" target="-blank" style="">View Demo</a>
							<button class="bdi__gl-item-button  button  button-primary  js-bdi-gl-import-data" value="<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Import', 'bdi' ); ?></button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div id="js-bdi-modal-content"></div>

	<?php endif; ?>

	<p class="bdi__ajax-loader  js-bdi-ajax-loader">
		<span class="spinner"></span> <?php esc_html_e( 'Importing, please wait!', 'bdi' ); ?>
	</p>

	<div class="bdi__response  js-bdi-ajax-response"></div>
</div>
