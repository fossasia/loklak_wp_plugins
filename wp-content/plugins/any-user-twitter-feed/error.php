<?php
	/**
	* @package		Any User Twitter Feed
	* @copyright	Web Design Services. All rights reserved. All rights reserved.
	* @license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	*/

?>

<div id="wds">
<?php if(isset($curlDisabled)): ?>
Your PHP doesn't have cURL extension enabled. Please contact your host and ask them to enable it.
<?php else: ?>
It seems that widget parameters haven't been configured properly. Please make sure that you are using a valid twitter username or query, and
that you have inserted the correct authentication keys. Detailed instructions are written on the widget settings page.
<?php endif; ?>
</div>
