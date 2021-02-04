<?php
// +---------------------------------------------------------------------------+
// | скрипт парсинга температуры                                               |
// +---------------------------------------------------------------------------+
// | Author: Roman Gromoglasov <r.gromoglasov@mail.ru>                         |
// +---------------------------------------------------------------------------+

  $dblocation = "server";
  $dbname = "db_name";
  $dbuser = "user";
  $dbpasswd = "password";

  $link = mysqli_connect($dblocation, $dbuser, $dbpasswd);  
  if (!$link)  
  {  
    echo "<p>К сожалению, не доступен сервер mySQL</p>";  
    exit();  
  }  
  if (!mysqli_select_db($link,$dbname) )  
  {  
    echo "<p>К сожалению, не доступна база данных</p>";  
    exit();  
  }  


// получаем содержимое нужной нам страницы в переменную $content
$content = file_get_contents('http://pogoda.ngs.ru/informer?js=no');
// Определяем позицию строки, до которой нужно все отрезать (используем функцию strpos()).
$pos = strpos($content, '</strong>   	<br />   	');
// Отрезаем все, что идет до нужной нам позиции
$content = substr($content, $pos+strlen('</strong>   	<br />   	'));
// Точно таким же образом находим позицию второй строки
$pos = strpos($content, ' В°C,');
// Отрезаем нужное количество символов от нулевого
$content = substr($content, 0, $pos);
// выводим полученную строку.
$content = str_replace(',','.',$content);
$content = str_replace('&minus;','-',$content);
echo $content;

//Пишем в БД полученный результат
$query="INSERT INTO reestr.temperature_ngs (Dte, T) VALUES (CURRENT_TIMESTAMP, '$content');";
$result=mysqli_query($link, $query) or
die(mysqli_errno($link).mysqli_error($link));

?>