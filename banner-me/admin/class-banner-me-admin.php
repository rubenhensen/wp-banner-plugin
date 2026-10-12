<?php

class Banner_Me_Admin {

	private $banner_me;
	private $version;

	public function __construct( $banner_me, $version ) {
		$this->banner_me = $banner_me;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->banner_me, plugin_dir_url( __FILE__ ) . 'css/banner-me-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->banner_me, plugin_dir_url( __FILE__ ) . 'js/banner-me-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function banner_options_page() {
		add_menu_page(
			'Banner Me',
			'Banner Me',
			'manage_options',
			'banner-me',
			array( $this, 'render_admin_page' ),
			'dashicons-table-row-before',
			65
		);
	}

	public function render_admin_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';

		if ( $action === 'edit' ) {
			$this->render_edit_view();
		} else {
			$this->render_list_view();
		}
	}

	private function render_list_view() {
		$banners = $this->get_banners();
		$today = current_time( 'Y-m-d' );

		usort( $banners, function( $a, $b ) {
			return strcmp( $a['from_date'], $b['from_date'] );
		});

		$new_url = admin_url( 'admin.php?page=banner-me&action=edit' );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Banner Me</h1>
			<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action">Nieuwe banner</a>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['message'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
					<?php
					$msg = sanitize_text_field( $_GET['message'] );
					if ( $msg === 'saved' ) {
						echo 'Banner opgeslagen.';
					} elseif ( $msg === 'deleted' ) {
						echo 'Banner verwijderd.';
					}
					?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( empty( $banners ) ) : ?>
				<p>Nog geen banners aangemaakt.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Gesloten van</th>
							<th>Gesloten t/m</th>
							<th>Tekst</th>
							<th>Kleur</th>
							<th>Status</th>
							<th>Acties</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $banners as $banner ) :
						$status = $this->get_status( $banner, $today );
						$edit_url = admin_url( 'admin.php?page=banner-me&action=edit&id=' . urlencode( $banner['id'] ) );
						$delete_url = wp_nonce_url(
							admin_url( 'admin.php?page=banner-me&action=delete&id=' . urlencode( $banner['id'] ) ),
							'banner_me_delete_' . $banner['id']
						);
						$display_text = ! empty( $banner['custom_text'] )
							? mb_strimwidth( $banner['custom_text'], 0, 60, '...' )
							: $this->get_default_text( $banner );
					?>
						<tr>
							<td><?php echo esc_html( $this->format_date_dutch( $banner['from_date'] ) ); ?></td>
							<td><?php echo esc_html( $this->format_date_dutch( $banner['to_date'] ) ); ?></td>
							<td><?php echo esc_html( $display_text ); ?></td>
							<td><span class="banner-me-color-swatch" style="background-color: <?php echo esc_attr( $banner['color'] ); ?>;"></span></td>
							<td><span class="banner-me-status banner-me-status--<?php echo esc_attr( $status['class'] ); ?>"><?php echo esc_html( $status['label'] ); ?></span></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>">Bewerken</a> |
								<a href="<?php echo esc_url( $delete_url ); ?>" class="banner-me-delete" onclick="return confirm('Weet je het zeker?');">Verwijderen</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_edit_view() {
		$banner = null;
		$is_new = true;

		if ( isset( $_GET['id'] ) ) {
			$id = sanitize_text_field( $_GET['id'] );
			$banners = $this->get_banners();
			foreach ( $banners as $b ) {
				if ( $b['id'] === $id ) {
					$banner = $b;
					$is_new = false;
					break;
				}
			}
		}

		$defaults = array(
			'id'          => '',
			'from_date'   => '',
			'to_date'     => '',
			'custom_text' => '',
			'color'       => '#3c8302',
		);
		$banner = wp_parse_args( $banner ?: array(), $defaults );

		$list_url = admin_url( 'admin.php?page=banner-me' );
		?>
		<div class="wrap">
			<h1><?php echo $is_new ? 'Nieuwe banner' : 'Banner bewerken'; ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<?php wp_nonce_field( 'banner_me_save', 'banner_me_nonce' ); ?>
				<input type="hidden" name="action" value="banner_me_save">
				<input type="hidden" name="banner_id" value="<?php echo esc_attr( $banner['id'] ); ?>">

				<table class="form-table">
					<tr>
						<th><label for="from_date">Gesloten van</label></th>
						<td><input type="date" id="from_date" name="from_date" value="<?php echo esc_attr( $banner['from_date'] ); ?>" required></td>
					</tr>
					<tr>
						<th><label for="to_date">Gesloten t/m</label></th>
						<td><input type="date" id="to_date" name="to_date" value="<?php echo esc_attr( $banner['to_date'] ); ?>" required></td>
					</tr>
					<tr>
						<th><label for="custom_text">Aangepaste tekst</label></th>
						<td>
							<input type="text" id="custom_text" name="custom_text" class="regular-text" value="<?php echo esc_attr( $banner['custom_text'] ); ?>" placeholder="Bijv. Gesloten van 15 juli t/m 5 augustus">
							<p class="description">Laat leeg voor de standaard tekst: "Gesloten van [datum] t/m [datum]"</p>
						</td>
					</tr>
					<tr>
						<th><label for="color">Kleur</label></th>
						<td><input type="color" id="color" name="color" value="<?php echo esc_attr( $banner['color'] ); ?>"></td>
					</tr>
				</table>

				<p class="description" style="margin-bottom: 16px;">De banner wordt automatisch zichtbaar 2 weken voor de "gesloten van" datum en verdwijnt na de "gesloten t/m" datum.<br>
				In dezelfde periode staat de sluiting ook onder de tabel met openingstijden. In de week zelf staat bij de gesloten dagen "Gesloten op [datum]".</p>

				<?php submit_button( 'Opslaan' ); ?>
				<a href="<?php echo esc_url( $list_url ); ?>">Annuleren</a>
			</form>
		</div>
		<?php
	}

	public function handle_form_submission() {
		// Handle save
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'banner_me_save' ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Geen toestemming.' );
			}
			if ( ! wp_verify_nonce( $_POST['banner_me_nonce'], 'banner_me_save' ) ) {
				wp_die( 'Ongeldige nonce.' );
			}

			$banner_id   = sanitize_text_field( $_POST['banner_id'] );
			$from_date   = sanitize_text_field( $_POST['from_date'] );
			$to_date     = sanitize_text_field( $_POST['to_date'] );
			$custom_text = sanitize_text_field( $_POST['custom_text'] );
			$color       = sanitize_hex_color( $_POST['color'] );

			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_date ) || ! strtotime( $from_date ) ) {
				wp_die( 'Ongeldige "gesloten van" datum.' );
			}
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to_date ) || ! strtotime( $to_date ) ) {
				wp_die( 'Ongeldige "gesloten t/m" datum.' );
			}
			if ( ! $color ) {
				$color = '#3c8302';
			}

			$banners = $this->get_banners();
			$is_new = empty( $banner_id );

			$banner_data = array(
				'id'          => $is_new ? 'bm_' . time() : $banner_id,
				'from_date'   => $from_date,
				'to_date'     => $to_date,
				'custom_text' => $custom_text,
				'color'       => $color,
			);

			if ( $is_new ) {
				$banners[] = $banner_data;
			} else {
				foreach ( $banners as $i => $b ) {
					if ( $b['id'] === $banner_id ) {
						$banners[ $i ] = $banner_data;
						break;
					}
				}
			}

			$this->save_banners( $banners );
			wp_safe_redirect( admin_url( 'admin.php?page=banner-me&message=saved' ) );
			exit;
		}

		// Handle delete
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'banner-me'
			&& isset( $_GET['action'] ) && $_GET['action'] === 'delete'
			&& isset( $_GET['id'] )
		) {
			$id = sanitize_text_field( $_GET['id'] );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Geen toestemming.' );
			}
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'banner_me_delete_' . $id ) ) {
				wp_die( 'Ongeldige nonce.' );
			}

			$banners = $this->get_banners();
			$banners = array_values( array_filter( $banners, function( $b ) use ( $id ) {
				return $b['id'] !== $id;
			}));
			$this->save_banners( $banners );
			wp_safe_redirect( admin_url( 'admin.php?page=banner-me&message=deleted' ) );
			exit;
		}
	}

	public function maybe_migrate() {
		$db_version = get_option( 'banner_me_db_version', '0' );
		if ( version_compare( $db_version, '2.0', '>=' ) ) {
			return;
		}

		$old_text  = get_option( 'banner_text' );
		$old_start = get_option( 'banner_start_date' );
		$old_end   = get_option( 'banner_end_date' );

		$existing = get_option( 'banner_me_banners', array() );

		if ( ( $old_text !== false || $old_start !== false || $old_end !== false ) && empty( $existing ) ) {
			$migrated = array(
				'id'          => 'bm_' . time(),
				'from_date'   => $old_start ?: '',
				'to_date'     => $old_end ?: '',
				'custom_text' => $old_text ?: '',
				'color'       => '#3c8302',
			);
			update_option( 'banner_me_banners', array( $migrated ) );
		}

		delete_option( 'banner_text' );
		delete_option( 'banner_visible' );
		delete_option( 'banner_start_date' );
		delete_option( 'banner_end_date' );
		update_option( 'banner_me_db_version', '2.0' );
	}

	private function get_banners() {
		return get_option( 'banner_me_banners', array() );
	}

	private function save_banners( $banners ) {
		update_option( 'banner_me_banners', $banners );
	}

	private function get_status( $banner, $today ) {
		$visible_from = date( 'Y-m-d', strtotime( $banner['from_date'] . ' -14 days' ) );

		if ( $today > $banner['to_date'] ) {
			return array( 'label' => 'Verlopen', 'class' => 'expired' );
		}
		if ( $today >= $visible_from && $today <= $banner['to_date'] ) {
			return array( 'label' => 'Actief', 'class' => 'active' );
		}
		return array( 'label' => 'Gepland', 'class' => 'scheduled' );
	}

	private function get_default_text( $banner ) {
		return 'Gesloten van ' . $this->format_date_dutch( $banner['from_date'] ) . ' t/m ' . $this->format_date_dutch( $banner['to_date'] );
	}

	private function format_date_dutch( $date_str ) {
		if ( empty( $date_str ) ) {
			return '';
		}
		$months = array(
			'januari', 'februari', 'maart', 'april', 'mei', 'juni',
			'juli', 'augustus', 'september', 'oktober', 'november', 'december',
		);
		$ts = strtotime( $date_str );
		$day = date( 'j', $ts );
		$month = $months[ (int) date( 'n', $ts ) - 1 ];
		$year = date( 'Y', $ts );
		$current_year = current_time( 'Y' );

		if ( $year !== $current_year ) {
			return $day . ' ' . $month . ' ' . $year;
		}
		return $day . ' ' . $month;
	}
}
