<?php $uniqid = uniqid();
echo '<p><a onclick="jQuery(\'.wds-instructions'.$uniqid.'\').toggle();" href="#">Show/hide instructions how to set the widget options</a></p>';
echo '
<div class="wds-instructions'.$uniqid.'" style="display: none;">
<h1>Important</h1><h2 style="color: red">To use this widget, please follow the steps bellow:</h2>
	<p>1) Register at <a href="https://dev.twitter.com/apps/new" target="_blank">https://dev.twitter.com/apps/new</a>
	 and create a new app.</p>
	<p>2) After registering, fill in App name, e.g. "_domain name_ App", description, e.g "My Twitter App", and write the address of your website. Check "I agree" next to their terms of service
	  and click "create your Twitter application"</p>
	<p>3) After this you app will be created. Click "Create my access token" and you should see at the bottom "access token" and "access token secret". Refresh the page if you don\'t see them.
	<p>4) Copy to widget settings "Consumer key", "Consumer secret", "Access token" and "Access secret"</p>
	<h2>FAQ</h2>
	<p><strong><span style="color: #000;">Q:</span></strong> Why do I have to trouble with all of this?<br />
		<strong><span style="color: #000;">A:</span></strong> Twitter is removing access for all unauthorized requests, so every extension which wants to connect to Twitter
	 must use authentication, otherwise it will stop working (many already have).</p>
	<p><strong><span style="color: #000;">Q:</span></strong> My widget doesn\'t work!<br />
		<strong><span style="color: #000;">A:</span></strong> Make sure that you have copied the correct keys. If widget type is set to timeline, make sure you chose a valid Twitter username. If widget type
		 is set to search, Twitter may return error if search query is extremely complex.</p>
	<p><strong><span style="color: #000;">Q:</span></strong> Do you cache results?<br />
		<strong><span style="color: #000;">A:</span></strong> Yes, but you should almost always see the latest tweets. Widget will always try to get the latest tweets and save them to a cache. In case of a problem, tweets will be retrived from the cache.
		 For high traffic sites (more than 10.000 visits per day), you may occasionally get tweets from the cache, as Twitter doesn\'t allow more than 180 requests per 15 minutes.
		  If you use more requests than allowed, widget will display latest saved tweets from the cache, until new 15 minute window opens.</p>
		 </div>';