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

<h2>Эти файлы в папке /In будут обработаны и сохранены в папке /Out</h2>
    <?php
        foreach (glob($_SERVER['DOCUMENT_ROOT'].'/In/*.php') as $fileName) {
            echo '<p class="yellow">/In/'.basename($fileName).'</p>';
        }
    ?>
<p>для запуска нажмите кнопку Старт</p>

    <form method="post" action="/">
        <input type="hidden" name="start" value="1"/>
        <input type="submit" value="Старт"/>
    </form>

</body>
</html>