<?php

include_once( __DIR__  . '/Class/Beautifier.php');


if(isset($_POST['start'])):

    foreach (glob(__DIR__ . '/In/*.php') as $fileName) {
        Beautifier::work(file_get_contents($fileName),basename($fileName));
    }

    include_once( __DIR__  . '/tpl/_result.php');

else:

    include_once( __DIR__  . '/tpl/_form.php');

endif;


?>
