<?php

class Banner_Me_Public {

	private $banner_me;
	private $version;

	public function __construct( $banner_me, $version ) {
		$this->banner_me = $banner_me;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->banner_me, plugin_dir_url( __FILE__ ) . 'css/banner-me-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->banner_me, plugin_dir_url( __FILE__ ) . 'js/banner-me-public.js', array( 'jquery' ), $this->version, false );
	}

	public function add_banner() {
		$banners = get_option( 'banner_me_banners', array() );
		if ( empty( $banners ) ) {
			return;
		}

		$today = current_time( 'Y-m-d' );

		// Filter to visible banners
		$visible = array();
		foreach ( $banners as $banner ) {
			if ( empty( $banner['from_date'] ) || empty( $banner['to_date'] ) ) {
				continue;
			}
			$visible_from = date( 'Y-m-d', strtotime( $banner['from_date'] . ' -14 days' ) );
			if ( $today >= $visible_from && $today <= $banner['to_date'] ) {
				$visible[] = $banner;
			}
		}

		if ( empty( $visible ) ) {
			return;
		}

		// Pick the one with the closest (earliest) from_date
		usort( $visible, function( $a, $b ) {
			return strcmp( $a['from_date'], $b['from_date'] );
		});
		$banner = $visible[0];

		// Generate text
		if ( ! empty( $banner['custom_text'] ) ) {
			$text = $banner['custom_text'];
		} else {
			$text = 'Gesloten van ' . $this->format_date_dutch( $banner['from_date'] ) . ' t/m ' . $this->format_date_dutch( $banner['to_date'] );
		}

		$color = ! empty( $banner['color'] ) ? $banner['color'] : '#3c8302';

		?>
		<div class="banner-me-alert" style="background-color: <?php echo esc_attr( $color ); ?>;">
			<div class="banner-me-alert-text">
				<span><?php echo esc_html( $text ); ?></span>
				<span class="banner-me-alert-close" onclick="this.closest('.banner-me-alert').style.display='none';">&times;</span>
			</div>
		</div>
		<?php
	}

	private function format_date_dutch( $date_str ) {
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
