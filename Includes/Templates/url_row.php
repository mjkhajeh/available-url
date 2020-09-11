<div id="availableurl_template_url_row">
	<table>
		<tbody>
			<tr class="<?php echo "{$this->PREFIX}{$template_type}" ?>_url_row" id="<?php echo "{$this->PREFIX}{$template_type}" ?>_url_{{index}}" data-id="{{index}}" style="display:none">
				<td>
					<input type="text" name="<?php echo $this->PREFIX ?>title[]" id="<?php echo $this->PREFIX ?>title_{{index}}" value="" disabled>
				</td>
				<td>
					<input type="url" name="<?php echo $this->PREFIX ?>url[]" id="<?php echo $this->PREFIX ?>url_{{index}}" placeholder="<?php _e( "http://... or https://...", 'availableurl' ) ?>" value="" disabled>
				</td>
				<td>
					<select name="<?php echo $this->PREFIX ?>settings_{{index}}[]" id="<?php echo $this->PREFIX ?>settings_{{index}}" class="" data-placeholder="<?php _e( "Select settings", "availableurl" ) ?>" multiple data-width="100%" disabled>
						<?php
						if( $url_settings ) {
							foreach( $url_settings as $setting_id => $setting_data ) {
								?>
								<option value="<?php echo $setting_id ?>"><?php echo $setting_data['title'] ?></option>
								<?php
							}
						}
						?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
</div>