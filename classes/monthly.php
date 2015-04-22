<?php
class FacetWP_Facet_Monthly {

	function __construct() {
		$this->label = __( 'Monthly archive', 'fwp' );
	}

	/**
	 * Load the available choices
	 */
	function load_values( $params ) {
		global $wpdb;

		$facet = $params['facet'];

		// Where
		$where_clause = $params['where_clause'];

		// Orderby
		$orderby = 'f.facet_display_value DESC';
		if( 'asc' === $facet['orderby'] ) {
			$orderby = 'f.facet_display_value ASC';
		}

		// Limit
		$limit = 10;
		if( absint( $facet['count'] ) > 0 ) {
			$limit = absint( $facet['count'] );
		}

		$orderby = apply_filters( 'facetwp_facet_orderby', $orderby, $facet );
		$where_clause = apply_filters( 'facetwp_facet_where', $where_clause, $facet );

		$sql = "
        SELECT DATE_FORMAT(f.facet_value, '%Y-%m') as facet_value, f.facet_display_value, COUNT(*) AS counter
        FROM {$wpdb->prefix}facetwp_index f
        WHERE f.facet_name = '{$facet['name']}' $where_clause
        GROUP BY f.facet_value
        ORDER BY $orderby
        LIMIT $limit";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Generate the facet HTML
	 */
	function render( $params ) {

		$output = '';
		$facet = $params['facet'];
		$values = (array) $params['values'];
		$selected_values = (array) $params['selected_values'];

		$label_any = empty( $facet['label_any'] ) ? __( 'Any', 'fwp' ) : sprintf( __( '%s', 'fwp' ), $facet['label_any'] );

		$output .= '<select class="facetwp-monthly">';
		$output .= '<option value="">' . esc_attr( $label_any ) . '</option>';

		foreach ( $values as $result ) {
			$selected = in_array( $result->facet_value, $selected_values ) ? ' selected' : '';

			$display_value = date_i18n( 'F Y', strtotime( $result->facet_display_value ) );
			// Determine whether to show counts
			$show_counts = apply_filters( 'facetwp_facet_dropdown_show_counts', true );
			if ( $show_counts ) {
				$display_value .= " ($result->counter)";
			}

			$output .= '<option value="' . $result->facet_value . '"' . $selected . '>' . $display_value . '</option>';
		}

		$output .= '</select>';
		return $output;
	}

	/**
	 * Filter the query based on selected values
	 */
	function filter_posts( $params ) {
		global $wpdb;

		$output = array();
		$facet = $params['facet'];
		$selected_values = $params['selected_values'];

		// Convert the selected value into an array
		// "0000-00" => array( year, month );
		$dates = explode( '-', reset( $selected_values ) );

		if( count( $dates ) < 2 ) {
			$dates = array( date( 'Y' ), date( 'm' ) );
		}

		$sql = $wpdb->prepare( "SELECT DISTINCT post_id
            FROM {$wpdb->prefix}facetwp_index
            WHERE facet_name = %s
            AND YEAR(facet_value) = %d
            AND MONTH(facet_value) = %d
            ORDER BY facet_value DESC",
			$facet['name'],
			absint( $dates[0] ),
			absint( $dates[1] )
		);

		$output = $wpdb->get_col( $sql );

		return $output;
	}

	/**
	 * Output admin settings HTML
	 */
	function settings_html() { ?>
		<tr class="facetwp-conditional type-monthly">
			<td>
				<?php _e( 'Default label', 'fwp' ); ?>:
				<div class="facetwp-tooltip">
					<span class="icon-question">?</span>
					<div class="facetwp-tooltip-content">
						Customize the first option label (default: "Any")
					</div>
				</div>
			</td>
			<td>
				<input type="text" class="facet-label-any" value="<?php _e( 'Any', 'fwp' ); ?>" />
			</td>
		</tr>
		<tr class="facetwp-conditional type-monthly">
			<td>
				<?php _e( 'Archive order', 'fwp' ); ?>:
				<div class="facetwp-tooltip">
					<span class="icon-question">?</span>
					<div class="facetwp-tooltip-content">
						Customize the archives order (default: "Newest to Oldest")
					</div>
				</div>
			</td>
			<td>
				<select class="facet-orderby">
					<option value="desc" selected><?php _e( 'Newest to Oldest', 'fwp' ); ?></option>
					<option value="asc"><?php _e( 'Oldest to newest', 'fwp' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="facetwp-conditional type-monthly">
			<td>
				<?php _e('Count', 'fwp'); ?>:
				<div class="facetwp-tooltip">
					<span class="icon-question">?</span>
					<div class="facetwp-tooltip-content"><?php _e( 'The maximum number of facet choices to show', 'fwp' ); ?></div>
				</div>
			</td>
			<td><input type="text" class="facet-count" value="10" /></td>
		</tr>
	<?php }

	/**
	 * Output any admin scripts
	 */
	function admin_scripts() { ?>
		<script>
			(function($) {
				wp.hooks.addAction('facetwp/load/monthly', function($this, obj) {
					$this.find('.type-monthly .facet-label-any').val(obj['label_any']);
					$this.find('.type-monthly .facet-orderby').val(obj['orderby']);
					$this.find('.type-monthly .facet-count').val(obj['count']);
				});

				wp.hooks.addFilter('facetwp/save/monthly', function($this, obj) {
					obj['source'] = 'post_date';
					obj['label_any'] = $this.find('.type-monthly .facet-label-any').val();
					obj['orderby'] = $this.find('.type-monthly .facet-orderby').val();
					obj['count'] = $this.find('.type-monthly .facet-count').val();
					return obj;
				});

				wp.hooks.addAction('facetwp/change/monthly', function($this) {
					//hide the source selection
					$this.closest('.facetwp-facet').find('.name-source').hide();
				});
			})(jQuery);
		</script>
	<?php }

	/**
	 * Output any front-end scripts
	 */
	function front_scripts() { ?>
		<script>
			(function($) {
				wp.hooks.addAction('facetwp/refresh/monthly', function($this, facet_name) {
					var val = $this.find('.facetwp-monthly').val();
					FWP.facets[facet_name] = val ? [val] : [];
				});

				wp.hooks.addAction('facetwp/ready', function() {
					$(document).on('change', '.facetwp-facet .facetwp-monthly', function() {
						var $facet = $(this).closest('.facetwp-facet');
						if ('' != $facet.find(':selected').val()) {
							FWP.static_facet = $facet.attr('data-name');
						}
						FWP.autoload();
					});
				});
			})(jQuery);
		</script>
	<?php }
}