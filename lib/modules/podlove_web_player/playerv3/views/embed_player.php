<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php the_title() ?></title>
    <meta name='robots' content='noindex,follow' />
    <link href="<?php echo $css_path ?>/pwp-dark-green.min.css" rel="stylesheet" media="screen" type="text/css" />
    <link href="<?php echo $css_path ?>/vendor/progress-polyfill.css" rel="stylesheet" media="screen" type="text/css" />
</head>
<body>
    <?php echo (new \Podlove\Modules\PodloveWebPlayer\Playerv3\HTML5Printer($episode))->render(); ?>
    <script src="<?php echo $js_path ?>/vendor/html5shiv.min.js"></script>
    <script src="<?php echo $js_path ?>/vendor/jquery.min.js"></script>
    <script src="<?php echo $js_path ?>/vendor/progress-polyfill.min.js"></script>
    <script src="<?php echo $js_path ?>/podlove-web-player.min.js"></script>
    <script>
        $('audio').podlovewebplayer(<?php echo json_encode($player_config); ?>);
    </script>
</body>
</html>
