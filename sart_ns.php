<?php
// +---------------------------------------------------------------------------+
// | Скрипт анализа нештатных ситуаций на контролируемых инженерных системах   |
// +---------------------------------------------------------------------------+
// | Author: Roman Gromoglasov <r.gromoglasov@mail.ru>                         |
// +---------------------------------------------------------------------------+
set_time_limit(0);

//Подключаем скрипт коннекта к БД
require ('nconnectdb.php');

//Функция отправки в Telegram
function SendTM($msg)
	{
		$token = 'token';	
		$telegram_chat_id = 'chat_id';	
		file_get_contents('https://api.telegram.org/bot'. $token .'/sendMessage?chat_id='. $telegram_chat_id .'&text=' . urlencode($msg));
	}

//Функция добавления НС в БД
function AddNS($kod_sart, $tip_sart, $kod_ns, $sms_send, $adr, $nme, $comment, $link) 
	{
	//Если в данный момент НС нет, то сообщение не отправляем, но ситуацию, что Аварии нет - пишем.	
	if ($sms_send==0)
		{
			//НС - нет, смотрим отправлялось ли сообщение о НС ранее
			$msg = '';
			$Query_text="
				SELECT sms_send, msg
				FROM sart_ns_tmp
				WHERE kod_sart =$kod_sart
				AND kod_ns = $kod_ns
				ORDER BY id DESC 
				LIMIT 0 , 1
				";			
			$Query = mysqli_query($link, $Query_text);
			$pr = mysqli_fetch_row($Query);	
			
			
			//Если сообщение отправлялось, то надо отправить что НС устранена.
			if ($pr[0]==1)
				{
					if ($kod_ns==1) {$msg=hex2bin('E29C85').' '.$adr.':'.$nme.' - Устранена.Авария.ИТП.Сухой ход!';}
					if ($kod_ns==2) {$msg=hex2bin('E29C85').' '.$adr.':'.$nme.' - Устранена.Авария.Вент.Угроза заморозки!';}
					if ($kod_ns==3) {$msg=hex2bin('E29C85').' '.$adr.':'.$nme.' - Устранена.Авария.Элеватор.Угроза затопления!';}
					if ($kod_ns==4) {$msg=hex2bin('E29C85').' '.$adr.':'.$nme.' - Связь с контроллером восстановлена!';}	
					
					SendTM($msg);//Отправляем в телеграм														
				}
			
			//Пишем в БД - состояние
			$Query_text="
				INSERT INTO sart_ns_tmp
				(kod_sart, tip_sart, kod_ns, sms_send, msg, comment) 
				VALUES 
				($kod_sart,'$tip_sart',$kod_ns, $sms_send, '$msg', '$comment')
				";			
			$Query = mysqli_query($link, $Query_text);	
		} 
	//Если НС есть, то смотрим было ли до этого отправлено сообщение об этой НС.
	else
		{
			$Query_text="
				SELECT sms_send
				FROM sart_ns_tmp
				WHERE kod_sart =$kod_sart
				AND kod_ns = $kod_ns
				ORDER BY id DESC 
				LIMIT 0 , 1
				";			
			$Query = mysqli_query($link, $Query_text);
			$pr = mysqli_fetch_row($Query);
			if ($pr[0]==0)
				{
					//НС есть, сообщение не отправлялось. надо отправлять и писать в БД					
					if ($kod_ns==1) {$msg=hex2bin('F09F8698').' '.$adr.':'.$nme.' - Авария.ИТП.Сухой ход!';}
					if ($kod_ns==2) {$msg=hex2bin('F09F8698').' '.$adr.':'.$nme.' - Авария.Вент.Угроза заморозки!';}
					if ($kod_ns==3) {$msg=hex2bin('F09F8698').' '.$adr.':'.$nme.' - Авария.Элеватор.Угроза затопления!';}
					if ($kod_ns==4) {$msg=hex2bin('F09F8698').' '.$adr.':'.$nme.' - Потеряна связь с контроллером!';}
					
					$Query_text="					
						INSERT INTO sart_ns_tmp
						(kod_sart, tip_sart, kod_ns, sms_send, msg, comment) 
						VALUES 
						($kod_sart,'$tip_sart',$kod_ns, $sms_send, '$msg', '$comment')
						";											
					$Query = mysqli_query($link, $Query_text);	
					
					SendTM($msg);//Шлем в телеграм
				}
			else
				{
					//НС есть, сообщение отпралялось. 
					//Сообщение не отправляем, но факт анализа пишем в БД
					$Query_text="
						INSERT INTO sart_ns_tmp
						(kod_sart, tip_sart, kod_ns, sms_send, comment) 
						VALUES 
						($kod_sart,'$tip_sart',$kod_ns, $sms_send, '$comment')
						";
					$Query = mysqli_query($link, $Query_text);	
				
				}
		
		
		
		}
	
	}
	
	
	$Query_text="
		SELECT 
			kod_sart_ters, 		
			telefon_sms, 
			telefon_sms2,
			(SELECT Nme FROM sp_variant_isp WHERE sp_variant_isp.id = kod_variant),
			(SELECT Nme FROM sp_tip_sart WHERE sp_tip_sart.id = kod_tip_sart),	
			(SELECT Alarms FROM  sart_all_values_otp WHERE  kod_itp =kod_sart_ters) as Alarms,  
			@qual_1:=(SELECT QUALITY FROM  sart_all_values_otp WHERE  kod_itp =kod_sart_ters) as QUALITY,
			(SELECT Alarms FROM  vent_all_values WHERE  kod_itp =kod_sart_ters) as Alarms_vent,  
			@qual_2:=(SELECT QUALITY FROM  vent_all_values WHERE  kod_itp =kod_sart_ters) as QUALITY_vent,
			adress,
			nme,
			(SELECT Flood FROM  sart_all_values_otp WHERE  kod_itp =kod_sart_ters) as Flood,
			@Dte:=(SELECT Dte FROM  sart_all_values_otp WHERE  kod_itp =kod_sart_ters) as Dte,
			@Tme:=(SELECT Tme FROM  sart_all_values_otp WHERE  kod_itp =kod_sart_ters) as Dte,
			@time_1:=timestampdiff(minute, TIMESTAMP(@Dte, @Tme),CURRENT_TIMESTAMP()) as neopros_min_otp,
			@Dte_vent:=(SELECT Dte FROM  vent_all_values WHERE  kod_itp =kod_sart_ters) as Dte_vent,
			@Tme_vent:=(SELECT Tme FROM  vent_all_values WHERE  kod_itp =kod_sart_ters) as Dte_vent,
			@time_2:=timestampdiff(minute, TIMESTAMP(@Dte_vent, @Tme_vent),CURRENT_TIMESTAMP()) as neopros_min_vent,
			IF(@time_1 is NULL, @time_2, @time_1) as Neopros,
			IF(@qual_1 is NULL, @qual_2, @qual_1) as Quality
		FROM sart_reestr WHERE scada_tmp=1 AND vis=0 
		";
	$Query = mysqli_query($link, $Query_text);

	while ($pr=mysqli_fetch_row($Query))
		{	
			//Смотрим на показатели только, когда качество связи больше 40%
			if ($pr[19]>40)
				{		
					//ОТП Alarms 4 бит - сухой ход 0- ОК 1 - Плохо КОД НС = 1
					if ((($pr[4]=='Отопление')||($pr[4]=='Отопление,теплый пол'))&&($pr[3]!='МВ110'))
						{
							if ($pr[5][4]==1)
								{
									//Авария. Сухой ход ИТП
									AddNS($pr[0], $pr[4], 1, 1, $pr[9], $pr[10],'ИТП.Alarms[4]='.$pr[5][4], $link);   
								} 
								else 
								{
									//НЕТ Аварии. Сухой ход ИТП
									AddNS($pr[0], $pr[4], 1, 0, $pr[9], $pr[10],'ИТП.Alarms[4]='.$pr[5][4], $link);			
								} 
						}
						
					//Вент Alarms 0 бит - заморозка 0- ОК 1 -  Плохо КОД НС = 2
					if ($pr[4]=='Вентиляция')
						{										
							if ($pr[7][0]==1)
								{
									//Авария. Угроза Заморозки ИТП
									AddNS($pr[0], $pr[4], 2, 1, $pr[9], $pr[10],'Вент.Alarms[0]='.$pr[7][0], $link);   
								} else {
									//НЕТ Аварии. ВЕНТ
									AddNS($pr[0], $pr[4], 2, 0, $pr[9], $pr[10],'Вент.Alarms[0]='.$pr[7][0], $link);
								} 
					}
					
					//Элеваторный узел  0 - угроза затопления 2- ОК КОД НС = 3
					if ($pr[3]=='МВ110')
						{	
							if ($pr[11]==0)
								{
									//Авария. Угроза Затопления. Элеватор
									AddNS($pr[0], $pr[4], 3, 1, $pr[9], $pr[10],'МВ110.Затопление(Flood)='.$pr[11], $link);   
								} else {			
									//НЕТ Аварии. Элеватор
									AddNS($pr[0], $pr[4], 3, 0, $pr[9], $pr[10],'МВ110.Затопление(Flood)='.$pr[11], $link);
								} 
						}
				}
	
					//Контролллер долго не опрашивался КОД НС = 4
					//Долго не опрашивался - если нет связи более 45 минут или качество <40
					if (($pr[18]>45)||($pr[19]<40))
						{
							//Нет связи с контроллером
							AddNS($pr[0], $pr[4], 4, 1, $pr[9], $pr[10],'Связь. Условие сработало. Время неопроса:'.$pr[18].' Качество связи:'.$pr[19], $link);   
						} else {			
							//Есть связь с контроллером
							AddNS($pr[0], $pr[4], 4, 0, $pr[9], $pr[10],'Связь. Условие несработало. Время неопроса:'.$pr[18].' Качество связи:'.$pr[19], $link);							
						} 
	
		}
		
	mysqli_free_result($Query);

?>
