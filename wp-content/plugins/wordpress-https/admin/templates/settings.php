<?php
require_once('includes/template.php'); // WordPress Dashboard Functions
?>

<div class="wphttps-message-wrap" id="message-wrap"><div id="message-body"></div></div>

<div class="wrap" id="wphttps-main">
	<div id="icon-options-https" class="icon32"><br /></div>
	<h2><?php _e('HTTPS','wordpress-https'); ?></h2>

<?php
	wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false );
	wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false );
?>
	<div id="poststuff" class="columns metabox-holder">
		<div class="postbox-container column-primary">
<?php do_meta_boxes('toplevel_page_' . $this->getPlugin()->getSlug(), 'main', $this); ?>
		</div>
		<div class="postbox-container column-secondary">
<?php do_meta_boxes('toplevel_page_' . $this->getPlugin()->getSlug(), 'side', $this); ?>
		</div>
	</div>
	
	<div id="regex-help">
		<h3><?php _e('Expressions','wordpress-https'); ?></h3>
		<table class="regex-help">
			<tr>
				<td>[abc]</td>
				<td><?php _e('A single character: a, b, or c','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>[^abc]</td>
				<td><?php _e('Any single character <em>but</em> a, b, or c','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>[a-z]</td>
				<td><?php _e('Any character in the range a-z','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>[a-zA-Z]</td>
				<td><?php _e('Any character in the range a-z or A-Z (any alphabetical character)','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\s</td>
				<td><?php _e('Any whitespace character [ \t\n\r\f\v]','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\S</td>
				<td><?php _e('Any non-whitespace character [^ \t\n\r\f\v]','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\d</td>
				<td><?php _e('Any digit [0-9]','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\D</td>
				<td><?php _e('Any non-digit [^0-9]','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\w</td>
				<td><?php _e('Any word character [a-zA-Z0-9_]','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\W</td>
				<td><?php _e('Any non-word character [^a-zA-Z0-9_]','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\b</td>
				<td><?php _e('A word boundary between \w and \W','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>\B</td>
				<td><?php _e('A position that is not a word boundary','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>|</td>
				<td><?php _e('Alternation: matches either the subexpression to the left or to the right','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>()</td>
				<td><?php _e('Grouping: group all together for repetition operators','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>^</td>
				<td><?php _e('Beginning of the string','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>$</td>
				<td><?php _e('End of the string','wordpress-https'); ?></td>
			</tr>
		</table>
		<h3><?php _e('Repetition&#160;Operators','wordpress-https'); ?></h3>
		<table class="regex-help">
			<tr>
				<td>{n,m}</td>
				<td><?php _e('Match the previous item at least <em>n</em> times but no more than <em>m</em> times','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>{n,}</td>
				<td><?php _e('Match the previous item <em>n</em> or more times','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>{n}</td>
				<td><?php _e('Match exactly <em>n</em> occurrences of the previous item','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>?</td>
				<td><?php _e('Match 0 or 1 occurrences of the previous item {0,1}','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>+</td>
				<td><?php _e('Match 1 or more occurrences of the previous item {1,}','wordpress-https'); ?></td>
			</tr>
			<tr>
				<td>*</td>
				<td><?php _e('Match 0 or more occurrences of the previous item {0,}','wordpress-https'); ?></td>
			</tr>
		</table>
	</div>
</div>