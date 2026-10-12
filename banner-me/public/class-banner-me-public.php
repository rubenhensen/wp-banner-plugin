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
	 * Turns an opening hours table (rows that start with a Dutch weekday:
	 * "Maandag:", "Dinsdag", ...) into an overview for visitors. Today's row
	 * is marked. When a closure falls on an opening day between today and the
	 * end of next week, the table shows this week with dates instead, plus
	 * next week if that week has a closure; closed days read "Gesloten".
	 * Otherwise it stays the normal weekly scheme. Closures that are announced
	 * (same window as the banner) are listed below the table.
	 */
	public function apply_closures_to_hours_table( $html, $today ) {
		$pattern = '#<tr[^>]*>\s*<t[hd][^>]*>\s*((' . implode( '|', array_keys( $this->weekdays ) ) . ')\s*:?)\s*</t[hd]>\s*<td[^>]*>(.*?)</td>\s*</tr>#iu';
		if ( ! preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER ) ) {
			return $html;
		}

		// The normal scheme, as the owners keep it in the page
		$rows = array();
		foreach ( $matches as $m ) {
			$rows[ $this->weekdays[ strtolower( $m[2] ) ] ] = array(
				'label' => $m[1],
				'hours' => $m[3],
			);
		}

		$banners = $this->get_banners();
		$today_weekday = (int) date( 'N', strtotime( $today ) );
		$this_week = $this->get_week_dates( date( 'Y-m-d', strtotime( $today . ' -' . ( $today_weekday - 1 ) . ' days' ) ) );
		$next_week = $this->get_week_dates( date( 'Y-m-d', strtotime( $this_week[1] . ' +7 days' ) ) );

		$closed_this_week = $this->has_closed_day( $rows, $this_week, $banners, $today );
		$closed_next_week = $this->has_closed_day( $rows, $next_week, $banners, $today );

		if ( $closed_this_week || $closed_next_week ) {
			$tables = $this->render_hours_table( $rows, $today, $banners, 'Deze week', $this_week );
			if ( $closed_next_week ) {
				$tables .= $this->render_hours_table( $rows, $today, $banners, 'Volgende week', $next_week );
			}
		} else {
			$tables = $this->render_hours_table( $rows, $today, $banners );
		}

		$announced = $this->get_announced_banners( $today );
		if ( ! empty( $announced ) ) {
			$tables .= '<p class="banner-me-hours-note"><strong>Afwijkende openingstijden</strong>';
			foreach ( $announced as $banner ) {
				$tables .= '<br>' . esc_html( $this->get_banner_text( $banner ) );
			}
			$tables .= '</p>';
		}

		$html = preg_replace_callback( '#<table[^>]*>.*?</table>#is', function() use ( $tables ) {
			return $tables;
		}, $html, 1 );

		$processor = new WP_HTML_Tag_Processor( $html );
		if ( $processor->next_tag( 'figure' ) ) {
			$processor->add_class( 'banner-me-hours' );
		}
		return $processor->get_updated_html();
	}

	/**
	 * Without $dates the table is the normal scheme, with the owners' own
	 * labels; with $dates (weekday => Y-m-d) each row gets its date.
	 */
	private function render_hours_table( $rows, $today, $banners, $caption = '', $dates = array() ) {
		$html = $dates ? '<table class="banner-me-week">' : '<table>';
		if ( $caption ) {
			$html .= '<caption>' . esc_html( $caption ) . '</caption>';
		}
		$html .= '<tbody>';

		$today_weekday = (int) date( 'N', strtotime( $today ) );
		foreach ( $rows as $weekday => $row ) {
			$date = isset( $dates[ $weekday ] ) ? $dates[ $weekday ] : '';
			$label = $date ? esc_html( ucfirst( $this->format_day_dutch( $date, true ) ) ) : $row['label'];
			$hours = $row['hours'];
			if ( $date && $this->is_closed( $row, $date, $banners ) ) {
				$hours = '<strong>Gesloten</strong>';
			}

			$is_today = $date ? $date === $today : $weekday === $today_weekday;
			if ( $is_today ) {
				$html .= '<tr class="banner-me-today"><th aria-current="date"><span class="banner-me-today-wrap">' . $label . ' <span class="banner-me-today-label">Vandaag</span></span></th>';
			} else {
				$html .= '<tr><th>' . $label . '</th>';
			}
			$html .= '<td>' . $hours . '</td></tr>';
		}

		return $html . '</tbody></table>';
	}

	/**
	 * Whether one of the given days, from today on, is normally open but
	 * falls in a closure.
	 */
	private function has_closed_day( $rows, $dates, $banners, $today ) {
		foreach ( $dates as $weekday => $date ) {
			if ( $date >= $today && isset( $rows[ $weekday ] ) && $this->is_closed( $rows[ $weekday ], $date, $banners ) ) {
				return true;
			}
		}
		return false;
	}

	private function is_closed( $row, $date, $banners ) {
		if ( strtolower( trim( wp_strip_all_tags( $row['hours'] ) ) ) === 'gesloten' ) {
			return false;
		}
		foreach ( $banners as $banner ) {
			if ( $date >= $banner['from_date'] && $date <= $banner['to_date'] ) {
				return true;
			}
		}
		return false;
	}

	// Monday through Sunday of the week starting at $monday, as weekday => Y-m-d
	private function get_week_dates( $monday ) {
		$dates = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$dates[ $i + 1 ] = date( 'Y-m-d', strtotime( $monday . ' +' . $i . ' days' ) );
		}
		return $dates;
	}

	private function get_banners() {
		$banners = get_option( 'banner_me_banners', array() );
		return array_filter( $banners, function( $banner ) {
			return ! empty( $banner['from_date'] ) && ! empty( $banner['to_date'] );
		});
	}

	/**
	 * Banners that are visible on the given day: from 2 weeks before the
	 * "gesloten van" date up to and including the "gesloten t/m" date,
	 * earliest first.
	 */
	private function get_announced_banners( $today ) {
		$visible = array();
		foreach ( $this->get_banners() as $banner ) {
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
		if ( $banner['from_date'] === $banner['to_date'] ) {
			return 'Gesloten op ' . $this->format_day_dutch( $banner['from_date'] );
		}
		return 'Gesloten van ' . $this->format_day_dutch( $banner['from_date'] ) . ' t/m ' . $this->format_day_dutch( $banner['to_date'] );
	}

	// "vrijdag 23 oktober", or "vrijdag 23 okt" with $short_month
	private function format_day_dutch( $date_str, $short_month = false ) {
		$weekday = array_search( (int) date( 'N', strtotime( $date_str ) ), $this->weekdays, true );
		return $weekday . ' ' . $this->format_date_dutch( $date_str, $short_month );
	}

	private function format_date_dutch( $date_str, $short_month = false ) {
		$months = $short_month ? array(
			'jan', 'feb', 'mrt', 'apr', 'mei', 'jun',
			'jul', 'aug', 'sep', 'okt', 'nov', 'dec',
		) : array(
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
