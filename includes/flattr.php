<?php

add_action('wp_head', 'podlove_flattr_script_head');

function podlove_flattr_script_head() {
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	(function() {
	    var s = document.createElement('script');
	    var t = document.getElementsByTagName('script')[0];

	    s.type = 'text/javascript';
	    s.async = true;
	    s.src = '//api.flattr.com/js/0.6/load.js?mode=auto&popout=0';

	    t.parentNode.insertBefore(s, t);
	 })();
	/* ]]> */
	</script>
	<?php
}