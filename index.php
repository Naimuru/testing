<HTML>
<HEAD><H1 align="center">Coordenadas iDirect IS37e</H1></HEAD>

<link rel="stylesheet" href="css/style1.css" type="text/css" media="screen"/>
<link rel="shortcut icon" href="images/favicon.ico">

<BODY>

<div align='left'><a><img src="images/IDIRECT.fw.png" border="0"></a></div>

<?php

error_reporting(0);

echo
"<FORM id='coordenadas' name='coordenadas' action='coordenadas.php' method='POST'>
<p>
<b><label>ID Modem: <input name='idModem' type='text' value='" . $_POST['idModem'] ."' id='modem' size='6'/></label>
</p>
<p>
<label>Radio en Kilometros: <input name='rango' type='text' value='" . $_POST['rango'] . "' id='rango' size='1'/></b> Km</label>
</p>";
if (isset($_POST['check_offline'])) echo "<input type='checkbox' name='check_offline' checked='yes' id='check_offline'>Mostrar Sitios Offline</input>";
else echo "<input type='checkbox' name='check_offline' value='" . $_POST['check_offline'] . "' id='check_offline'>Mostrar Sitios Offline</input>";
echo "
<p><input type='submit' name='Submit' value='Enviar'/></p>
</FORM>";


#Los datos de acceso:

$hostname = "172.4.4.3";
$usuario = "rouser";
$password = "1nf0rm3s1d1r3ct";
$basededatos = "nms";
$tabla = "NetModem";

#echo "Conectando con MySQL...<br>"; Banderita

$conexion = mysql_connect("$hostname", "$usuario", "$password");

if ($conexion==0)
echo "Lo sentimos, no se ha podido conectar con MySQL<br>";
else
{
	//echo "Se logrÛ conectar con MySQL"; Banderita
	echo "<br>";


	//echo "Conectando con la base de datos $basededatos...<br>"; Banderita
	$dbconnect = mysql_select_db("$basededatos",$conexion);
	if ($dbconnect==0)
	echo "Lo sentimos, no se ha podido conectar con la base datos: $basededatos<br>";
	else
	{	
		//echo "Conectado con la base datos: $basededatos<br>"; Banderita
		$idModem = $_POST['idModem'];
		$rango = $_POST['rango'];
		
		$consulta1="select N.NetworkID,G.LatDegrees,G.LatMinutes,G.LatSeconds,G.LongDegrees,G.LongMinutes,G.LongSeconds from NetModem N,GeoLocation G where N.LocationId=G.GeoLocationId AND N.ActiveStatus!=0 AND N.ModemSn = $idModem;";
		
		$resultado1 = mysql_query($consulta1,$conexion);
		while($rows = mysql_fetch_array($resultado1))
			{
				$redModem = $rows["NetworkID"];
				$latitudModem = ($rows["LatDegrees"] + $rows["LatMinutes"]/60 + $rows["LatSeconds"]/3600);
				$longitudModem = ($rows["LongDegrees"] + $rows["LongMinutes"]/60 + $rows["LongSeconds"]/3600);	
			}
		
		switch ($redModem) {
			case 10:
				$nombreRed = "BandaC_IS37e";
				break;
			case 17:
				$nombreRed = "IS37eK40";
				break;
			case 18:
				$nombreRed = "IS37eK42";
				break;
			default:
				$nombreRed = "IS37eK40V2";
		}
				
		if($idModem != null && $rango>0 && $redModem != null)
		{			
			//if($redModem == null)echo "Dato nulo";
			//else echo $redModem;
			$consulta="select N.ModemSn,N.DID,V.IpAddr,N.NetModemName,N.NetworkID,G.LatDegrees,G.LatMinutes,G.LatSeconds,G.LongDegrees,G.LongMinutes,G.LongSeconds from NetModem N,VLanRemote V,GeoLocation G where N.LocationId=G.GeoLocationId AND N.NetModemId = V.NetModemId AND N.NetworkID = $redModem AND N.ActiveStatus!=0 AND V.InterfaceId ='sat0' AND V.VLanID =1 order by G.LatDegrees,G.LatMinutes,G.LatSeconds,G.LongDegrees,G.LongMinutes,G.LongSeconds";
			
			$resultado = mysql_query($consulta,$conexion);
			//$rows = mysql_fetch_array($resultado);
					
			$snrSuma = 0;
			$txSuma = 0;
			$sitiosOnline = 0;
			$numero = 0;
			$contador = 0;
			$sitiosOffline = 0;

			echo					
					"<table align='center' class='bordered'>
					<tr>
					<th align='center'>MODEM</th>
					<th align='center'>IP TX</th>
					<th align='center'>NOMBRE</th>
					<th align='center'>NETWORK</th>
					<th align='center'>SNR</th>
					<th align='center'>TX POWER</th>
					<th align='center'>WARNINGS</th>
					<th align='center'>DISTANCIA (Km)</th>					
					</tr>";
					
			$contador++;
			
			
					
				while($row = mysql_fetch_array($resultado))
				{
					$latitud = ($row["LatDegrees"] + $row["LatMinutes"]/60 + $row["LatSeconds"]/3600);
					$longitud = ($row["LongDegrees"] + $row["LongMinutes"]/60 + $row["LongSeconds"]/3600);
					
					$DID = $row["DID"];										
									
					$distancia = round( distanciaGeodesica($latitudModem, $longitudModem, $latitud, $longitud), 2 );	

					if($distancia <= $rango)
					{						
						snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
						$snr = snmpget("172.4.4.3","public","1.3.6.1.4.1.13732.1.4.3.1.2.$DID");					
						$txpower = snmpget("172.4.4.3","public","1.3.6.1.4.1.13732.1.4.3.1.3.$DID");
						$warning = snmpget("172.4.4.3","public","1.3.6.1.4.1.13732.1.1.1.1.13.$DID");
						
						
						if($row["ModemSn"] == $idModem)
						{							
							echo "<tr bgcolor='#FCF3CF'>";
							
							echo "<td align=center><b><i>" . $row["ModemSn"] . "</i></b></td>";
							echo "<td><b><i>" . $row["IpAddr"] . "</i></b></td>";
							echo "<td><b><i>" . $row["NetModemName"] . "</i></b></td>";
							echo "<td><b><i>$nombreRed</i></b></td>";	
													
							if($snr == "" || $snr == "0.000000")
							{								
								echo "<td><font color='red'><b><i>Offline</i></b></font></td>";
								echo "<td><font color='red'><b><i>Offline</i></b></font></td>";
								echo "<td><font color='red'><b><i>Offline</i></b></font></td>";
								echo "<td><b><i>" . round( distanciaGeodesica($latitudModem, $longitudModem, $latitudModem, $longitudModem), 2 ) . "</i></b></td>";								
							}							
							else
							{
								echo "<td><b><i>" . round($snr, 2) . "</i></b></td>";
								echo "<td><b><i>" . round($txpower, 2) . "</i></b></td>";
								echo "<td><b><i>" . $warning . "</i></b></td>";
								echo "<td><b><i>" . round( distanciaGeodesica($latitudModem, $longitudModem, $latitudModem, $longitudModem), 2 ) . "</i></b></td>";								
							}
							$numero++;
						}
						else
						{
							if(isset($_POST['check_offline'])) //Si esta seleccionada la casilla de remotas Offline
							{
								echo "<tr>";
								echo "<td align=center>" . $row["ModemSn"] . "</td>";
								echo "<td>" . $row["IpAddr"] . "</td>";
								echo "<td>" . $row["NetModemName"] . "</td>";
								echo "<td>$nombreRed</td>";
														
								if($snr == "" || $snr == "0.000000")
								{
									echo "<td><font color='red'>Offline</font></td>";
									echo "<td><font color='red'>Offline</font></td>";
									echo "<td><font color='red'>Offline</font></td>";
									echo "<td><font color='Blue'>" . round( distanciaGeodesica($latitudModem, $longitudModem, $latitud, $longitud), 2 ) . "</font></td>";
									$sitiosOffline++;
								}
								else
								{
									echo "<td><font color='green'>" . round($snr, 2) . "</font></td>";
									echo "<td><font color='Brown'>" . round($txpower, 2) . "</font></td>";
									
									if($warning != "No Warnings")echo "<td><font color='orange'>" . $warning . "</font></td>";
									else echo "<td>No</td>";
									
									echo "<td><font color='Blue'>" . round( distanciaGeodesica($latitudModem, $longitudModem, $latitud, $longitud), 2 ) . "</font></td>";
									
									$sitiosOnline++;
								}
							}
							else
							{
								if($snr == "" || $snr == "0.000000")
								{	
								}
								else
								{
									echo "<tr>";
									echo "<td align=center>" . $row["ModemSn"] . "</td>";
									echo "<td>" . $row["IpAddr"] . "</td>";
									echo "<td>" . $row["NetModemName"] . "</td>";
									echo "<td>$nombreRed</td>";
									echo "<td><font color='green'>" . round($snr, 2) . "</font></td>";
									echo "<td><font color='Brown'>" . round($txpower, 2) . "</font></td>";						
									
									if($warning != "No Warnings")echo "<td><font color='orange'>" . $warning . "</font></td>";
									else echo "<td>No</td>";
										
									echo "<td><font color='Blue'>" . round( distanciaGeodesica($latitudModem, $longitudModem, $latitud, $longitud), 2 ) . "</font></td>";
										
									$sitiosOnline++;
								}							
							}
							
							$numero++;
							$snrSuma = $snrSuma + $snr;
							$txSuma = $txSuma + $txpower;
						}
					}
				}
			
				/*echo "<tr>
					<td colspan=\"3\" align='center'><font color='green'><b>Sitios Activos Cercanos: </b></font><font color='red'><b>" . ($numero-1) . "</b></font>
					<p><font size=2 color='green'><b>Sitios Offline: </b></font><font color='red'><b>" . $sitiosOffline . "</b></font></td>
					<td colspan=\"5\" align='center'><font color='green'><b>Promedio de SNR: </b></font><font color='red'><b>" . round(($snrSuma/$sitiosOnline), 2) . "</b></font>
					<p><font color='green'><b>Promedio Tx Power: </b></font><font color='red'><b>" . round(($txSuma/$sitiosOnline), 2) . "</b></font></td>
					</tr>";	*/			
			}
			else if($idModem=="" || $rango == "");
			else echo "<p align='center'><b>El modem no se encuentra dentro de la base de datos o est· inactivo</b></p>";
						
			mysql_free_result($resultado);			

		//} 
	}
}

function distanciaGeodesica($lat1, $long1, $lat2, $long2)
{
	$degtorad = 0.01745329; 
	$radtodeg = 57.29577951; 

	$dlong = ($long1 - $long2); 
	$dvalue = (sin($lat1 * $degtorad) * sin($lat2 * $degtorad)) 
	+ (cos($lat1 * $degtorad) * cos($lat2 * $degtorad) 
	* cos($dlong * $degtorad)); 

	$dd = acos($dvalue) * $radtodeg; 

	$miles = ($dd * 69.16); 
	$km = ($dd * 111.302); 

	return $km; 
}

/*function Distance($lat1, $lon1, $lat2, $lon2, $unit) { 
  
  $radius = 6378.137; // earth mean radius defined by WGS84
  $dlon = $lon1 - $lon2; 
  $distance = acos( sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($dlon))) * $radius; 

  if ($unit == "K") {
  		return ($distance); 
  } else if ($unit == "M") {
    	return ($distance * 0.621371192);
  } else if ($unit == "N") {
    	return ($distance * 0.539956803);
  } else {
    	return 0;
  }
}*/

mysql_close($conexion);


?>

</table>
</div>
<div>
<br><br><br><p align='center'><i>Developed By Milton Benavides M.</i></p>
</div>
<br/><br/><br/>
</BODY>
</HTML>
