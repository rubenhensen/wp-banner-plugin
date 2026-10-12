<?php

class Banner_Me_Public {

	private $banner_me;
	private $version;

	private $weekdays = array(
		'maandag'   => 1,
		'dinsdag'   => 2,
		'woensdag'  => 3,
		'donderdag' => 4,
		'vrijdag'   => 5,
		'zaterdag'  => 6,
		'zondag'    => 7,
	);

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
		$visible = $this->get_announced_banners( current_time( 'Y-m-d' ) );
		if ( empty( $visible ) ) {
			return;
		}

		// Show the one with the closest (earliest) from_date
		$banner = $visible[0];
		$text = $this->get_banner_text( $banner );
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

	public function update_hours_table( $block_content ) {
		return $this->apply_closures_to_hours_table( $block_content, current_time( 'Y-m-d' ) );
	}

	/**
	 * Applies the closures to an opening hours table: a table whose rows start
	 * with a Dutch weekday ("Maandag:", "Dinsdag", ...). Each row stands for
	 * the next occurrence of that weekday (today up to 6 days ahead); if that
	 * day is closed, its hours become "Gesloten op <datum>". Closures that are
	 * announced (same window as the banner) are listed below the table.
	 */
	public function apply_closures_to_hours_table( $html, $today ) {
		$banners = $this->get_announced_banners( $today );
		if ( empty( $banners ) ) {
			return $html;
		}

		$pattern = '#(<tr[^>]*>\s*<t[hd][^>]*>\s*(' . implode( '|', array_keys( $this->weekdays ) ) . ')\s*:?\s*</t[hd]>\s*<td[^>]*>)(.*?)(</td>)#iu';
		if ( ! preg_match( $pattern, $html ) ) {
			return $html;
		}

		$today_weekday = (int) date( 'N', strtotime( $today ) );

		$html = preg_replace_callback( $pattern, function( $m ) use ( $banners, $today, $today_weekday ) {
			$hours = $m[3];
			if ( strtolower( trim( wp_strip_all_tags( $hours ) ) ) === 'gesloten' ) {
				return $m[0];
			}

			$weekday = $this->weekdays[ strtolower( $m[2] ) ];
			$offset = ( $weekday - $today_weekday + 7 ) % 7;
			$date = date( 'Y-m-d', strtotime( $today . ' +' . $offset . ' days' ) );

			foreach ( $banners as $banner ) {
				if ( $date >= $banner['from_date'] && $date <= $banner['to_date'] ) {
					return $m[1] . '<strong>Gesloten op ' . esc_html( $this->format_date_dutch( $date ) ) . '</strong>' . $m[4];
				}
			}
			return $m[0];
		}, $html );

		$note = '<p class="banner-me-hours-note"><strong>Afwijkende openingstijden</strong>';
		foreach ( $banners as $banner ) {
			$note .= '<br>' . esc_html( $this->get_banner_text( $banner ) );
		}
		$note .= '</p>';

		return preg_replace( '#</table>#i', '</table>' . $note, $html, 1 );
	}

	/**
	 * Banners that are visible on the given day: from 2 weeks before the
	 * "gesloten van" date up to and including the "gesloten t/m" date,
	 * earliest first.
	 */
	private function get_announced_banners( $today ) {
		$banners = get_option( 'banner_me_banners', array() );

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

		usort( $visible, function( $a, $b ) {
			return strcmp( $a['from_date'], $b['from_date'] );
		});

		return $visible;
	}

	private function get_banner_text( $banner ) {
		if ( ! empty( $banner['custom_text'] ) ) {
			return $banner['custom_text'];
		}
		return 'Gesloten van ' . $this->format_date_dutch( $banner['from_date'] ) . ' t/m ' . $this->format_date_dutch( $banner['to_date'] );
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
