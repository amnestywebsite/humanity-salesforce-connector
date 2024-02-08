<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<style>.column-id{width:50px}</style>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
					<?php

					$this->list_table->prepare_items();
					$this->list_table->display();

					?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>
