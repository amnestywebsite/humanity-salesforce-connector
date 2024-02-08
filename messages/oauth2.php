<p>
<?php

echo wp_kses_post(
	sprintf(
		// translators: %1$s: line break, %2$s: documentation link
		__( 'You need to create a Connected App to generate your Client ID/Secret keys; see %2$s for further details.%1$s', 'aisc' ),
		'<br>',
		'<a href="https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/intro_oauth_and_connected_apps.htm" target="_blank" rel="noreferrer noopener">Salesforce</a>'
	)
);

?>
</p>

<p>
<?php
echo wp_kses_post(
	sprintf(
		// translators: %1$s: line break, %2$s: required permissions, %3$s: callback URL
		__( 'Please ensure that your Connected App has the following oAuth permissions: %2$s.%1$sThe Callback URL must be set to this: %3$s.', 'aisc' ),
		'<br>',
		'<code>api</code>, <code>refresh_token, offline_access</code>',
		sprintf( '<code>%s</code>', \Amnesty\Salesforce\OAuth2::callback() )
	)
);

?>
</p>
