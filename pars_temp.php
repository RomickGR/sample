<?php
// +---------------------------------------------------------------------------+
// | ������ �������� �����������                                               |
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
    echo "<p>� ���������, �� �������� ������ mySQL</p>";  
    exit();  
  }  
  if (!mysqli_select_db($link,$dbname) )  
  {  
    echo "<p>� ���������, �� �������� ���� ������</p>";  
    exit();  
  }  


// �������� ���������� ������ ��� �������� � ���������� $content
$content = file_get_contents('http://pogoda.ngs.ru/informer?js=no');
// ���������� ������� ������, �� ������� ����� ��� �������� (���������� ������� strpos()).
$pos = strpos($content, '</strong>   	<br />   	');
// �������� ���, ��� ���� �� ������ ��� �������
$content = substr($content, $pos+strlen('</strong>   	<br />   	'));
// ����� ����� �� ������� ������� ������� ������ ������
$pos = strpos($content, ' °C,');
// �������� ������ ���������� �������� �� ��������
$content = substr($content, 0, $pos);
// ������� ���������� ������.
$content = str_replace(',','.',$content);
$content = str_replace('&minus;','-',$content);
echo $content;

//����� � �� ���������� ���������
$query="INSERT INTO reestr.temperature_ngs (Dte, T) VALUES (CURRENT_TIMESTAMP, '$content');";
$result=mysqli_query($link, $query) or
die(mysqli_errno($link).mysqli_error($link));

?>