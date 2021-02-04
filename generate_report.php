<?php
// +---------------------------------------------------------------------------+
// | Скрипт формирования отчета в формате Excel.                               |
// +---------------------------------------------------------------------------+
// | Author: Roman Gromoglasov <r.gromoglasov@mail.ru>                         |
// +---------------------------------------------------------------------------+

session_start();
if (isset($_SESSION['us_id'])==0)
	{
		echo 'Доступ заперещен!';  
		exit;
	}
else
	{
		$us_id=$_SESSION['us_id'];	
	}

// Подключаем библиотеку для работы с Excel
set_include_path(get_include_path() .'/PhpExcel/Classes/');	
// Подключаем скрипт коннекта к MySql БД
require ('mysqlconnect.php');
// Подключаем скрипт коннекта к MSSQL БД
require ('connect.php');



mysqli_query($link, "SET NAMES utf8");

$Id_pribora=$_GET[id_pribora]; 
$Tip=$_GET[tip];
$kod=$_GET[kod]; 
$Dat_begin=$_GET[Dat_begin]; 
$Dat_end=$_GET[Dat_end]; 


//Если тип запрошенных данных - часовые, то дата начала это текущая дата минус день, если суточные, то начало месяца
if ($Dat_begin=='')
	{
		if (strpos($Tip,'hrs')==true)
			{
				$Dat_begin=date("d.m.Y", strtotime('-1 days'));
			} 
		else 
			{
				$Dat_begin=date("01.m.Y");
			}
	}

//Конечная дата - текущая дата
if ($Dat_end=='')
	{
		$Dat_end=date("d.m.Y");
	}

//Функция для получения ввода прибора учета
function GetVvod($pribor,$vvod) 
	{
	if (($pribor=="СПТ942")||($pribor=="СПТ943")||($pribor=="Эльф"))
		{
			if ($vvod=='1')
				{
					$buf="A.PODTIP = 'ТВ1'";
				}
			if ($vvod=='2')
				{
					$buf="A.PODTIP = 'ТВ2'";
				}
		}
	if (($pribor=="СПТ944")||($pribor=="СПТ941")||($pribor=="СПТ961_2")||($pribor=="СПТ961")||($pribor=="ТСРВ-034" )||($pribor=="ТСРВ-030" )||($pribor=="ВКТ-5")||($pribor=="TB7-5890(0)")||($pribor=="КМ-5-4")||($pribor=="КМ-5-1")||($pribor=="КМ-5-2"))
		{
			$buf="(A.PODTIP = 'ОВ' OR A.PODTIP = 'ОБЩ')";
		}
    
    if ($pribor=="ВКТ7")
		{
			if ($vvod=='1')
				{
					$buf="A.PODTIP = 'ВВОД 1'";
				}
			if ($vvod=='2')
				{
					$buf="A.PODTIP = 'ВВОД 2'";
				}
		}    
	if ($pribor=="ТЕПЛО-3")
		{
			$buf="A.PODTIP = 'OB'";
		}
	if ($pribor=="ВКТ-9")
		{
			$buf="A.PODTIP = 'Все'";
		}

	if (($pribor=="ТМК-Н20")||($pribor=="ТМК-Н120")||($pribor=="ТМК-Н130"))
		{
			$buf="A.PODTIP = 'TC1'";
		}				
    return $buf;
	}	

//Проверяем права на доступ к объеку
if ((GetPr($link,$us_id)==0) && ($Tip!='')) 
	{	
		$query="SELECT count(*) FROM  web_permissions_kd WHERE User_id='$us_id' AND kod_uu_ters='$kod'";
		$result=mysqli_query($link, $query) or
		die(mysqli_errno($link).mysqli_error($link));
		$dat=mysqli_fetch_row ($result);
		if ($dat[0]==0) 
			{	
				echo '
				<table table align="center" width="600" class="information">
				<tr>
				<td colspan="2" class="zag" align="center"><b>Отказано в доступе!</b></td>
				</tr>
				<tr valign="middle">
				<td align="center" class="zn">
				Вам отказано в доступе к данному прибору, свяжитесь с администратором системы : <a href="mailto:admin@ters54.ru"> [написать администратору]
				</td>
				<td ><img src="/img/logo.png" alt="Доступ к метрологическим показания приборов учета тепла ТЭРС"></td>
				</tr>
				</table>
				';
				exit;
			}	
	}

//Запрос из БД информации о приборе учета
$Query_info_text="SELECT abonent, adres, pribor, tip_otopl, tip_gvs, kod1_otp, kod1_gvs, kod1_hvs, kod2_otp, kod2_gvs, kod2_hvs FROM tsu WHERE kod_uu_ters='$kod' ";
$Query_info = mysqli_query($link, $Query_info_text);
$Info = mysqli_fetch_array($Query_info);

$prb = $Info[2];

switch ($Tip) {
	case "otp":
        $Tip_text="Отопление(Суточные)";
		$Tip_text2="OT(daily)";
        break;
	case "gvs":
        $Tip_text="ГВС(Суточные)";
		$Tip_text2="GVS(daily)";
        break;  
	case "hvs":
        $Tip_text="ХВС(Суточные)";
		$Tip_text2="HVS(daily)";
        break;
	case "otp_hrs":
        $Tip_text="Отопление(Часовые)";
		$Tip_text2="OT(hours)";
        break;
	case "gvs_hrs":
        $Tip_text="ГВС(Часовые)";
		$Tip_text2="GVS(hours)";
        break;
	case "hvs_hrs":
        $Tip_text="ХВС(Часовые)";
		$Tip_text2="HVS(hours)";
        break;		
}


include_once 'PHPExcel/IOFactory.php';
$objPHPExcel = PHPExcel_IOFactory::load("otchet.xls");
$objPHPExcel->setActiveSheetIndex(0);
$aSheet = $objPHPExcel->getActiveSheet();


// устанавливаем авто подбор высоты 
$aSheet->getRowDimension(4)->setRowHeight(-1);

$let = array(
	'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
	'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
);

$border3 = array(
	'borders'=>array(
		'allborders' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('rgb' => '000000')
		)
	)
);

$bold_style = array(
    'font' => array(
        'bold' => true
    )
);

$aSheet->setCellValue('C4',$row['abonent']);
$aSheet->setCellValue('A1','Отчет о потребленнии за период с '.$Dat_begin.' по '.$Dat_end,$DataFormatZag);
$aSheet->setCellValue('A2',$Tip_text,$DataFormatZag);
$aSheet->setCellValue('A4', $Info[0],$DataFormat);
$aSheet->setCellValue('C4', $Info[1],$DataFormat);
$aSheet->setCellValue('D4', $Info[2],$DataFormat);
$aSheet->setCellValue('E4', $Info[3],$DataFormat);
$aSheet->setCellValue('F4', $Info[4],$DataFormat);
$aSheet->setCellValue('G4', $Id_pribora,$DataFormat);

$aSheet->getStyle("A1:A2")->applyFromArray($bold_style);

//В зависимости от типа запрошенных данных и типа прибора учета получаем список параметров ПУ требуемых для отчета
//Cуточные - ОТ
if ($Tip=='otp')
	{
		if ($Info[5]>0)
			{
				$stroka_otp=$Info[5];$Vvod=GetVvod($Info[2],1);
			} 
		else if ($Info[8]>0)
			{
				$stroka_otp=$Info[8];$Vvod=GetVvod($Info[2],2);
			} 
		$Query_parametrs_text="SELECT `Work_time`,`T1`,`T2`,`T1T2`,`M1`,`M2`,`V1`,`V2`,`Q`,`P1`,`P2`,`NS` FROM values_otp WHERE Id='$stroka_otp'";
	}
//Cуточные - ГВС
if ($Tip=='gvs')
	{
		if ($Info[6]>0)
			{
				$stroka_otp=$Info[6];
				$Vvod=GetVvod($Info[2],1);
			} 
		else if ($Info[9]>0)
			{
				$stroka_otp=$Info[9];
				$Vvod=GetVvod($Info[2],2);
			} 
		$Query_parametrs_text="SELECT `Work_time`,`T1`,`T2`,`Q`,`M1`,`M2`,`V1`,`V2`,`V1V2`,`NS` FROM values_gvs WHERE Id='$stroka_otp'";
	}
//Cуточные - ХВС
if ($Tip=='hvs')	
	{
		if ($Info[7]>0)
			{
				$stroka_otp=$Info[7];
				$Vvod=GetVvod($Info[2],1);
			} 
		else if ($Info[10]>0)
			{
				$stroka_otp=$Info[10];
				$Vvod=GetVvod($Info[2],2);
			} 
		$Query_parametrs_text="SELECT `Work_time`,`V`,`NS` FROM values_hvs WHERE Id='$stroka_otp'";
	}
	
//Часовые - ОТ
if ($Tip=='otp_hrs')
	{
		if ($Info[5]>0)
			{
				$stroka_otp=$Info[5];
				$Vvod=GetVvod($Info[2],1);
			} 
		else if ($Info[8]>0)
			{
				$stroka_otp=$Info[8];
				$Vvod=GetVvod($Info[2],2);
			} 
		$Query_parametrs_text="SELECT `Work_time`,`T1`,`T2`,`T1T2`,`M1`,`M2`,`V1`,`V2`,`Q`,`P1`,`P2`,`NS` FROM values_otp_hrs WHERE Id='$stroka_otp'";
	}
	
//Часовые - ГВС	
if ($Tip=='gvs_hrs')
	{
		if ($Info[6]>0)
			{
				$stroka_otp=$Info[6];
				$Vvod=GetVvod($Info[2],1);
			} 
		else if ($Info[9]>0)
			{
				$stroka_otp=$Info[9];
				$Vvod=GetVvod($Info[2],2);
			} 
		$Query_parametrs_text="SELECT `Work_time`,`T1`,`T2`,`Q`,`M1`,`M2`,`V1`,`V2`,`V1V2`,`NS` FROM values_gvs_hrs WHERE Id='$stroka_otp'";
	}
//Часовые - ХВС}
if ($Tip=='hvs_hrs')	
	{	
		if ($Info[7]>0)
			{
				$stroka_otp=$Info[7];
				$Vvod=GetVvod($Info[2],1);
			} 
		else if ($Info[10]>0)
			{
				$stroka_otp=$Info[10];
				$Vvod=GetVvod($Info[2],2);
			} 
		$Query_parametrs_text="SELECT `Work_time`,`V`,`NS` FROM values_hvs_hrs WHERE Id='$stroka_otp'";
	}

	

$Query_parametrs = mysqli_query($link, $Query_parametrs_text);
$Parametrs = mysqli_fetch_array($Query_parametrs);
$params = array ();//Для формирования списка параметров
$params_name = array ();//Для вывода в шапку таблицы
$params_opisanie = array (); //Описание параметров потребления
$podpis = array ();//Для вывода единиц измерения
$params_sum_flag = array ();//Для задания флага возможно ли суммирование данного параметра 
$flag_ns=0;


if (strpos($Tip,'hrs')==true) 
	{
		$params[]="CONVERT(varchar(10), DT, 104) + ' ' + LEFT(CONVERT(varchar(10), DT, 108),5) as Dte ";
	} 
else 
	{
		$params[]="CONVERT(varchar(10), DT, 104) as Dte ";
	}

$podpis[]=' дд.мм.гггг';$params_name[]='Дата';$params_sum_flag[]='0';$params_opisanie[]='Дата измерения параметра';

//Если ОТ
if (($Tip=='otp')||($Tip=='otp_hrs'))
	{
		if  ($Parametrs[0]!='') {$params[]=$Parametrs[0]." AS 'Tw'"; $podpis[]='час';$params_name[]='Tw';$params_sum_flag[]='1';$params_opisanie[]='Время штатной работы прибора';};
		if  ($Parametrs[1]!='') {$params[]="CAST(ROUND(".$Parametrs[1].", 3) AS float) AS 'T1'"; $podpis[]='°С';$params_name[]='T1';$params_sum_flag[]='0';$params_opisanie[]='Температура на подающем трубопроводе';};
		if  ($Parametrs[2]!='') {$params[]="CAST(ROUND(".$Parametrs[2].", 3) AS float) AS 'T2'"; $podpis[]='°С';$params_name[]='T2';$params_sum_flag[]='0';$params_opisanie[]='Температура на обратном трубопроводе';};
		if  ($Parametrs[3]!='') {$params[]="CAST(ROUND(".$Parametrs[3].", 3) AS float) AS 'T1-T2'"; $podpis[]='°С';$params_name[]='T1-T2';$params_sum_flag[]='0';$params_opisanie[]='Разница температур на трубопроводах';};
		if  ($Parametrs[4]!='') {$params[]="CAST(ROUND(".$Parametrs[4].", 3) AS float) AS 'M1'"; $podpis[]='тонн';$params_name[]='M1';$params_sum_flag[]='1';$params_opisanie[]='Масса воды, пройденной по подающему трубопроводу';};
		if  ($Parametrs[5]!='') {$params[]="CAST(ROUND(".$Parametrs[5].", 3) AS float) AS 'M2'"; $podpis[]='тонн';$params_name[]='M2';$params_sum_flag[]='1';$params_opisanie[]='Масса воды, пройденной по обратному трубопроводу';};
		if  ($Parametrs[6]!='') {$params[]="CAST(ROUND(".$Parametrs[6].", 3) AS float) AS 'V1'"; $podpis[]='м3';$params_name[]='V1';$params_sum_flag[]='1';$params_opisanie[]='Объем воды, пройденной по подающему трубопроводу';};
		if  ($Parametrs[7]!='') {$params[]="CAST(ROUND(".$Parametrs[7].", 3) AS float) AS 'V2'"; $podpis[]='м3';$params_name[]='V2';$params_sum_flag[]='1';$params_opisanie[]='Объем воды, пройденной по обратному трубопроводу';};
		if  ($Parametrs[8]!='') {$params[]="CAST(ROUND(".$Parametrs[8].", 3) AS float) AS 'Q'";  $podpis[]='ГКал';$params_name[]='Q';$params_sum_flag[]='1';$params_opisanie[]='Количество тепловой энергии';};
		if  ($Parametrs[9]!='') {$params[]="CAST(ROUND(".$Parametrs[9].", 3) AS float) AS 'P1'"; $podpis[]='кг/см2';$params_name[]='P1';$params_sum_flag[]='0';$params_opisanie[]='Давление в падающем трубопроводе';};
		if  ($Parametrs[10]!='') {$params[]="CAST(ROUND(".$Parametrs[10].", 3) AS float) AS 'P2'"; $podpis[]='кг/см2';$params_name[]='P2';$params_sum_flag[]='0';$params_opisanie[]='Давление в обратном трубопроводе';};
		if  ($Parametrs[11]!='') {$params[]="[A2].[dbo].[KNSTOSPT](".$Parametrs[11].") AS 'NS'"; $podpis[]='код';$params_name[]='НС';$params_sum_flag[]='0';$params_opisanie[]='Код нештатной ситуации '; $flag_ns=1;};
	}
	
//Если ГВС
if (($Tip=='gvs')||($Tip=='gvs_hrs'))
	{
		if  ($Parametrs[0]!='') {$params[]=$Parametrs[0]." AS 'Tw'"; $podpis[]='час';$params_name[]='Tw';$params_sum_flag[]='1';$params_opisanie[]='Время штатной работы прибора';};
		if  ($Parametrs[1]!='') {$params[]="CAST(ROUND(".$Parametrs[1].", 3) AS float) AS 'T1'"; $podpis[]='°С';$params_name[]='T1';$params_sum_flag[]='0';$params_opisanie[]='Температура на подающем трубопроводе';};
		if  ($Parametrs[2]!='') {$params[]="CAST(ROUND(".$Parametrs[2].", 3) AS float) AS 'T2'"; $podpis[]='°С';$params_name[]='T2';$params_sum_flag[]='0';$params_opisanie[]='Температура на обратном трубопроводе';};
		if  ($Parametrs[3]!='') {$params[]="CAST(ROUND(".$Parametrs[3].", 3) AS float) AS 'Q'"; $podpis[]='ГКал';$params_name[]='Q';$params_sum_flag[]='1';$params_opisanie[]='Количество тепловой энергии';};
		if  ($Parametrs[4]!='') {$params[]="CAST(ROUND(".$Parametrs[4].", 3) AS float) AS 'M1'"; $podpis[]='тонн';$params_name[]='M1';$params_sum_flag[]='1';$params_opisanie[]='Масса воды, пройденной по подающему трубопроводу';};
		if  ($Parametrs[5]!='') {$params[]="CAST(ROUND(".$Parametrs[5].", 3) AS float) AS 'M2'"; $podpis[]='тонн';$params_name[]='M2';$params_sum_flag[]='1';$params_opisanie[]='Масса воды, пройденной по обратному трубопроводу';};
		if  ($Parametrs[6]!='') {$params[]="CAST(ROUND(".$Parametrs[6].", 3) AS float) AS 'V1'"; $podpis[]='м3';$params_name[]='V1';$params_sum_flag[]='1';$params_opisanie[]='Объем воды, пройденной по подающему трубопроводу';};
		if  ($Parametrs[7]!='') {$params[]="CAST(ROUND(".$Parametrs[7].", 3) AS float) AS 'V2'"; $podpis[]='м3';$params_name[]='V2';$params_sum_flag[]='1';$params_opisanie[]='Объем воды, пройденной по обратному трубопроводу';};
		if  ($Parametrs[8]!='') {$params[]="CAST(ROUND(".$Parametrs[8].", 3) AS float) AS 'V3'"; $podpis[]='м3';$params_name[]='Vпот. ГВС';$params_sum_flag[]='1';$params_opisanie[]='Объем потребления ГВС';};
		if ($Info[4]=='Циркуляция'){$params[]="CAST(ROUND(".$Parametrs[6]." - ".$Parametrs[7].", 3) AS float) AS 'V4'"; $params_gp[]="CAST(ROUND(".$Parametrs[6]."-".$Parametrs[7].", 3) AS float) AS 'V4'"; $podpis[]='м3';$params_name[]='V1-V2';$params_sum_flag[]='1';$params_opisanie[]='Разница объема воды V1-V2';};
		if  ($Parametrs[9]!='') {$params[]="[A2].[dbo].[KNSTOSPT](".$Parametrs[9].") AS 'NS'"; $podpis[]='код';$params_name[]='НС';$params_sum_flag[]='0';$params_opisanie[]='Код нештатной ситуации '; $flag_ns=1;};
	}
	
//Если ХВС
if (($Tip=='hvs')||($Tip=='hvs_hrs'))
	{
		if  ($Parametrs[0]!='') {$params[]=$Parametrs[0]." AS 'Tw'"; $podpis[]='час';$params_name[]='Tw';$params_sum_flag[]='1';$params_opisanie[]='Время штатной работы прибора';};
		if  ($Parametrs[1]!='') {$params[]="CAST(ROUND(".$Parametrs[1].", 3) AS float) AS 'V1'"; $podpis[]='м3';$params_name[]='V1';$params_sum_flag[]='1';$params_opisanie[]='Объем воды, пройденной по трубопроводу';};
		if  ($Parametrs[2]!='') {$params[]="[A2].[dbo].[KNSTOSPT](".$Parametrs[2].") AS 'NS'"; $podpis[]='код';$params_name[]='НС';$params_sum_flag[]='0';$params_opisanie[]='Код нештатной ситуации '; $flag_ns=1;};
	}



$params_list=implode(', ', $params);

mysqli_free_result($Query_parametrs);


$tip_arhiv='Архив суточный';
if (strpos($Tip,'hrs')==true)
	{
		$tip_arhiv='Архив часовой';
	}

//Запрос имени таблицы с данными и номера записи для данного прибора учета (БД - MS SQL)
$Work_info_query="
	SELECT  DISTINCT
		A.V_TABLE,
		A.REC_NO
	FROM 
		[A2].[dbo].[TABLE1] A INNER JOIN [R3].[dbo].[TPOINT] R3 ON A.NUMER = R3.NUMER 
	WHERE
		A.TIP = '$tip_arhiv' AND 
		A.NUMER = '$Id_pribora'  AND 
		$Vvod AND
		A.PRIBOR = '$prb'
	";
	
$Work_info = sqlsrv_query($conn, $Work_info_query);
$row = sqlsrv_fetch_array($Work_info);
$V_Table=$row[0];
$Rec_no=$row[1];
sqlsrv_free_stmt($Work_info);

//Запрос метрологических данных с ПУ (БД - MS SQL)
$queryL="
	SELECT ".$params_list." FROM [A2].[dbo].$V_Table($Rec_no,'$Dat_begin 00:00:00', '$Dat_end 23:59:59') ORDER BY DT;
	"; 
$resultL=sqlsrv_query($conn, $queryL) ;
$sze=count($params);
$params_sum = array ();


//Выводим имена параметров
for ($i=0;$i<$sze;$i++)
		{
			$aSheet->setCellValue($let[$i].'5',$params_name[$i]);				
		}				

//Выводим шапку с единицами измерения
for ($i=0;$i<$sze;$i++)
		{		
			$aSheet->setCellValue($let[$i].'6',$podpis[$i]);		
		}				

//Цикл вывода значения параметров и суммирования
$k=7; //номер строки с которой начинается вывод данных


while ($row3=sqlsrv_fetch_array($resultL))
	{	
		for ($i=0;$i<$sze;$i++)
			{				
				$aSheet->setCellValue($let[$i].$k,$row3[$i]);	
				if ($params_sum_flag[$i]=='1')
					{
						$params_sum[$i]=$params_sum[$i]+$row3[$i];
					}
			}
	$k++;
	}


	

for ($i=0;$i<$sze;$i++)
	{

		if ($i==0)
			{			
				$aSheet->setCellValue($let[$i].$k,'Итого:');			
			}
		
		if ($params_sum_flag[$i]==1)
			{ 
				$aSheet->setCellValue($let[$i].$k, $params_sum[$i]);			
			} 
		
		else if (($params_sum_flag[$i]!=1) &&($i!=0))
			{
				//$aSheet->setCellValue($let[$i].$k, $params_sum[$i]);
			}

	}		
				


$aSheet->getStyle("A5:".$let[$sze-1].$k)->applyFromArray($border3);
$aSheet->getStyle("A".$k.":".$let[$sze-1].$k)->applyFromArray($bold_style);

//создаем объект класса-писателя
include("PHPExcel/Writer/Excel5.php");
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
//выводим заголовки
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="'.''.$Id_pribora.'_'.$Tip_text2.'_Report_'.$Dat_begin.'_'.$Dat_end.'.xls'.'"');
header('Cache-Control: max-age=0');
//выводим в браузер таблицу с бланком
$objWriter->save('php://output');

?> 

