<?php
/**
 * Removes everything Pratiks 3D Model Embedder stored. Runs when the plugin is deleted from wp-admin.
 *
 * The plugin stores no options. It adds GLB files converted from STL and OBJ
 * uploads, and an `pratiks_3d_model_embedder` entry in each model's attachment
 * metadata; both are removed here. The uploaded models themselves, and any
 * poster images, are the site's media and stay.
 *
 * @package PratikLondhe\Embed3DModels
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Removes the plugin's files and metadata on the current site.
 */
function pratiks_3d_model_embedder_uninstall_site(): void {
	$page = 1;
	do {
		$ids = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_status'      => 'inherit',
				'post_mime_type'   => 'model/gltf-binary,model/gltf+json,model/vnd.usdz+zip,model/stl,model/obj',
				'posts_per_page'   => 100,
				'paged'            => $page,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		foreach ( $ids as $id ) {
			$metadata = get_post_meta( (int) $id, '_wp_attachment_metadata', true );
			if ( ! is_array( $metadata ) || ! isset( $metadata['pratiks_3d_model_embedder'] ) ) {
				continue;
			}

			$name = (string) ( $metadata['pratiks_3d_model_embedder']['glb'] ?? '' );
			$file = (string) get_attached_file( (int) $id );
			if ( '' !== $name && '' !== $file ) {
				$path = trailingslashit( dirname( $file ) ) . wp_basename( $name );
				if ( '.glb' === strtolower( substr( $path, -4 ) ) && file_exists( $path ) ) {
					wp_delete_file( $path );
				}
			}

			unset( $metadata['pratiks_3d_model_embedder'] );
			wp_update_attachment_metadata( (int) $id, $metadata );
		}

		++$page;
		$found = count( $ids );
	} while ( 100 === $found );
}

if ( is_multisite() ) {
	$pratiks_3d_model_embedder_page = 1;
	do {
		$pratiks_3d_model_embedder_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => ( $pratiks_3d_model_embedder_page - 1 ) * 100,
			)
		);
		foreach ( $pratiks_3d_model_embedder_sites as $pratiks_3d_model_embedder_site ) {
			switch_to_blog( (int) $pratiks_3d_model_embedder_site );
			pratiks_3d_model_embedder_uninstall_site();
			restore_current_blog();
		}
		++$pratiks_3d_model_embedder_page;
		$pratiks_3d_model_embedder_found = count( $pratiks_3d_model_embedder_sites );
	} while ( 100 === $pratiks_3d_model_embedder_found );
} else {
	pratiks_3d_model_embedder_uninstall_site();
}
