<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <title>PHP-Beautifier</title>

  <style>
       .green {
           color: green;
       }
       .yellow {
           color: #A68900;
       }
      
   </style>
</head>
<body>

<h2>Эти файлы были обработаны и созданы в папке /Out</h2>
    <?php
        foreach (Beautifier::$result_files as $fileName) {
            echo '<p class="green">/Out/'.basename($fileName).'</p>';
        }
    ?>

    <form method="get" action="/">
        <input type="submit" value="OK"/>
    </form>

</body>
</html>