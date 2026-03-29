<?php
/**
 * Template admin/views/common/list/chart_script.php
 *
 * @package Forminator
 */

$days_array    = array();
$default_array = array();

for ( $h = 30; $h >= 0; $h-- ) {
	$time                   = strtotime( '-' . $h . ' days' );
	$date                   = gmdate( 'Y-m-d', $time );
	$default_array[ $date ] = 0;
	$days_array[]           = gmdate( 'M j, Y', $time );
}

foreach ( $this->getModules() as $module ) {

	if ( 0 === $module['entries'] ) {
		$submissions_data = $default_array;
	} else {
		$submissions       = Forminator_Form_Entry_Model::get_form_latest_entries_count_grouped_by_day( $module['id'], $args['start_date'] );
		$submissions_array = wp_list_pluck( $submissions, 'entries_amount', 'date_created' );
		$submissions_data  = array_merge( $default_array, array_intersect_key( $submissions_array, $default_array ) );
	}

	// Get highest value.
	$highest_submission = max( $submissions_data );

	// Calculate canvas top spacing.
	$canvas_top_spacing = $highest_submission + 8;
	?>
	<script>

		var ctx = document.getElementById( 'forminator-module-<?php echo esc_attr( $module['id'] ); ?>-stats' );
		var monthDays = [ '<?php echo wp_kses_post( implode( "', '", $days_array ) ); ?>' ],
			submissions = [ <?php echo esc_attr( implode( ', ', $submissions_data ) ); ?> ];

		var chartData = {
			labels: monthDays,
			datasets: [{
				label: '<?php esc_html_e( 'Submissions', 'forminator' ); ?>',
				data: submissions,
				backgroundColor: [
					'#E1F6FF'
				],
				borderColor: [
					'#17A8E3'
				],
				borderWidth: 2,
				pointRadius: 0,
				pointHitRadius: 20,
				pointHoverRadius: 5,
				pointHoverBorderColor: '#17A8E3',
				pointHoverBackgroundColor: '#17A8E3'
			}]
		};

		var chartOptions = {
			maintainAspectRatio: false,
			legend: {
				display: false
			},
			scales: {
				xAxes: [{
					display: false,
					gridLines: {
						color: 'rgba(0, 0, 0, 0)'
					}
				}],
				yAxes: [{
					display: false,
					gridLines: {
						color: 'rgba(0, 0, 0, 0)'
					},
					ticks: {
						beginAtZero: false,
						min: 0,
						max: <?php echo esc_attr( $canvas_top_spacing ); ?>,
						stepSize: 1
					}
				}]
			},
			elements: {
				line: {
					tension: 0
				},
				point: {
					radius: 0
				}
			},
			tooltips: {
				custom: function( tooltip ) {
					if ( ! tooltip ) return;
					// disable displaying the color box;.
					tooltip.displayColors = false;
				},
				callbacks: {
					title: function( tooltipItem, data ) {
						return tooltipItem[0].yLabel + " Submissions";
					},
					label: function( tooltipItem, data ) {
						return tooltipItem.xLabel;
					},
					// Set label text color.
					labelTextColor:function( tooltipItem, chart ) {
						return '#AAAAAA';
					}
				}
			},
			plugins: {
				datalabels: {
					display: false
				}
			}
		};

		if (ctx) {
			var myChart = new Chart(ctx, {
				type: 'line',
				fill: 'start',
				data: chartData,
				plugins: [
					ChartDataLabels
				],
				options: chartOptions
			});
		}


	</script>
	<?php
	// Only for QUizzes.
	if ( isset( $module['has_leads'] ) && $module['has_leads'] ) {

		if ( ! isset( $module['leads'] ) || 0 === $module['leads'] ) {
			$submissions_data = $default_array;
		} else {
			$submissions       = Forminator_Form_Entry_Model::get_form_latest_lead_entries_count_grouped_by_day( $module['id'], $args['start_date'] );
			$submissions_array = wp_list_pluck( $submissions, 'entries_amount', 'date_created' );
			$submissions_data  = array_merge( $default_array, array_intersect_key( $submissions_array, $default_array ) );
		}

		// Get highest value.
		$highest_submission = max( $submissions_data );

		// Calculate canvas top spacing.
		$canvas_top_spacing = $highest_submission + 8;
		?>
		<script>
			var ctx = document.getElementById( 'forminator-module-<?php echo esc_attr( $module['leads_id'] ); ?>-stats' );

			var monthDays = [ '<?php echo wp_kses_post( implode( "', '", $days_array ) ); ?>' ],
				submissions = [ <?php echo esc_attr( implode( ', ', $submissions_data ) ); ?> ];

			var chartData = {
				labels: monthDays,
				datasets: [{
					label: 'Submissions',
					data: submissions,
					backgroundColor: [
						'#E1F6FF'
					],
					borderColor: [
						'#17A8E3'
					],
					borderWidth: 2,
					pointRadius: 0,
					pointHitRadius: 20,
					pointHoverRadius: 5,
					pointHoverBorderColor: '#17A8E3',
					pointHoverBackgroundColor: '#17A8E3'
				}]
			};

			var chartOptions = {
				maintainAspectRatio: false,
				legend: {
					display: false
				},
				scales: {
					xAxes: [{
						display: false,
						gridLines: {
							color: 'rgba(0, 0, 0, 0)'
						}
					}],
					yAxes: [{
						display: false,
						gridLines: {
							color: 'rgba(0, 0, 0, 0)'
						},
						ticks: {
							beginAtZero: false,
							min: 0,
							max: <?php echo esc_attr( $canvas_top_spacing ); ?>,
							stepSize: 1
						}
					}]
				},
				elements: {
					line: {
						tension: 0
					},
					point: {
						radius: 0
					}
				},
				tooltips: {
					custom: function( tooltip ) {
						if ( ! tooltip ) return;
						// disable displaying the color box;.
						tooltip.displayColors = false;
					},
					callbacks: {
						title: function( tooltipItem, data ) {
							return tooltipItem[0].yLabel + " Submissions";
						},
						label: function( tooltipItem, data ) {
							return tooltipItem.xLabel;
						},
						// Set label text color.
						labelTextColor:function( tooltipItem, chart ) {
							return '#AAAAAA';
						}
					}
				},
				plugins: {
					datalabels: {
						display: false
					}
				}
			};

			if (ctx) {
				var myChart = new Chart(ctx, {
					type: 'line',
					fill: 'start',
					data: chartData,
					plugins: [
						ChartDataLabels
					],
					options: chartOptions
				});
			}


		</script>
		<?php
	}
}
