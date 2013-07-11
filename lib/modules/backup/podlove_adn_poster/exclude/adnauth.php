<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" class="wp-toolbar"  lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<style type="text/css">
		* {
			margin: 0px;
			padding: 0px;
		}
		
		body {
			font: 14px/1.5 Arial, Tahoma, sans;
			color: #4f4f4f;
		}
		
		h1 {
			font-weight: normal;
			background-color: #eee;
			padding: 10px 30px 10px 30px;
			border-bottom: 1px solid #dbdbdb;
		}
		
		body div {
			padding: 10px 30px 10px 30px;
			width: 500px;
		}
		
		ol {
			margin: 1em 0px 1em 2em;
		}
		
		p {
			margin: 1em 0px 1em 0px;
		}
		
		a, a:visited {
			font-weight: bold;
			color: #2580a5;
		}
		
		a:hover {
			text-decoration: none;
			color: #000;
		}
		
		textarea {
			width: 380px;
			height: 80px;
			background-color: #f3f1f1;
			border: 1px solid #dbdbdb;
			padding: 20px;
		}
		
	</style>

</head>
<body>
<?php

	if($_GET["code"] == "" AND $_GET["step"] == "") {
		print_r("Something went wrong.");
	} else {
		if($_GET["step"] == "3") {
			echo "<h1>Podlove App.net Poster: Authorization Step 3</h1>";
			echo "<div>";
			echo "<p>Finally you need to copy the auth code right below in the \"Auth Key\" field in the Modules section and save the changes. 
			Make shure you copy the whole code. You can close this windows afterwards. Thanks for trusting <em>Podlove Publisher</em>.</p>";
			echo "<textarea id=\"feld\">".$_GET["code"]."</textarea>";
		}
		
		if($_GET["step"] == "1") {
			echo "<h1>Podlove App.net Poster: Authorization Step 1</h1>";
			echo "<div>";
			echo "<p>To authorize <em>Podlove</em>, to broadcast every new episode you need to auth it on App.net. 
			This can be done as explained right below. Read the intructions carefully before you start the auth process.</p>";
			echo "<ol>
				<li>Make sure you are logged in at App.net. If you are logged in click on \"continue\".</li>
				<li>On the App.net authorization page click the \"AUTHORIZE\" button, if you would like to allow <em>Podlove</em> to broadcast new episodes for you.</li>
				<li>Copy the resulting auth code into the \"Auth Key\" field in the Modules section.</li>
			</ol>";
			echo "<p><a href='https://account.app.net/oauth/authenticate?client_id=mvDM8wTmV8aEWFnJg7Nq6KLxb23QdnFn&response_type=token&scope=write_post&redirect_uri=http%3A%2F%2Fwww2.synthx.de%2Fadnauth.php?step=3'>Continue</a></p>";
		}		
	}

?>
</div>
	<script type="text/javascript">
		document.getElementById("feld").innerHTML = window.location.hash.substring(14);
	</script>
</body>
</html>