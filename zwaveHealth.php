<?php

	/** VERIFICATION DES VARIABLES OPTIONNELLES **/
	$zwave_port = "8083";
	//Si le port est indiqué on le set, sinon c'est celui par defaut
	if(isset($argv[4])){
		$zwave_port = $argv[4];
	}
	
	//Folder json temporaire
	$zwave_health_json = '/tmp/zwave_health.json';
	if(isset($argv[5])){
		$zwave_health_json = $argv[5];
	}
	
	/** VERIFICATION DES VARIABLES OBLIGATOIRES **/
	
	//Verification de la présence des arguments obligatoires
	if(!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3])){
		echo "Tout les arguments ne sont pas présents.";
		
	}
	//Verification si l'ip est correcte
	else if(!preg_match("#([0-9]{1,3}\.){3}[0-9]{1,3}#", $argv[1])){
		echo "L'adresse IP semble être incorrecte.";
		echo "<br />";
		echo "Merci de vérifier.";
	}
	//Verification si la clé API jeedom est correcte
	else if(!preg_match("#[0-9A-Za-z]#", $argv[2])){
		echo "La clé API semble être incorrecte.";
		echo "<br />";
		echo "Merci de vérifier.";
	}
	//Verification sur le code affichage
	else if(!preg_match("#[0-3]{1}#", $argv[3])){
		echo "Le code affichage semble être incorrect.";
		echo "<br />";
		echo "Merci de vérifier.";
	}
	else{
		//IP jeedom (parametre)
		$ip = $argv[1]; 		
		//API Key pour acces page sante ZWave
		$api_key = $argv[2];
		//Affichage sur le dashboard: 0 - Modules Deads / 1 - Modules Timeout + Dead / 2 - Tout les modules
		$show_option = $argv[3];
		
		//USER CONFIRME - NE MODIFIER CES VALEURS QUE SI BESOIN
		//Adresse de la page santé
		$api_health = "/ZWaveAPI/Run/network.GetHealth%28%29";
		
		/** TRAITEMENT **/
		
		//Construction de l'URL de la page santé zwave
		$url = "token:".$api_key."@".$ip.":".$zwave_port.$api_health;
		
		//initialisation curl
		$curl = curl_init();

		//Setting CURLOPT_RETURNTRANSFER variable to 1 will force cURL
		//not to print out the results of its query.
		//Instead, it will return the results as a string return value
		//from curl_exec() instead of the usual true/false.
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		//connexion effectuée, on accède à la page de santé zwave
		curl_setopt($curl, CURLOPT_URL, $url);

		//execute la requête
		$content = curl_exec($curl);

		//enregistre le contenu de la page santé dans un fichier json
		file_put_contents($zwave_health_json, $content);

		//change les droits sur le fichier - écriture
		chmod($zwave_health_json, 0777);

		//convertis certains caractères pour ne pas avoir de souci d'affichage dans les tuiles
		//correspondance trouvée ici http://www.eteks.com/tips/tip3.html
		$bad_letters = array('\u00e0','\u00e2','\u00e4','\u00e7','\u00e8','\u00e9','\u00ea','\u00eb','\u00ee','\u00ef','\u00f4','\u00f6','\u00f9','\u00fb','\u00fc'); //lettres à remplacer
		$good_letters   = array('&agrave;','&acirc;','&auml;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&icirc;','&iuml;','&ocirc;','&ouml;','&ugrave;','&ucirc;','&uuml;'); //lettres de remplacement
		$text   = file_get_contents($zwave_health_json); //récupération du contenu du fichier
		$output  = str_replace($bad_letters, $good_letters, $text); // remplacement des caractères
		//réécris le fichier avec les caractères remplacés
		file_put_contents($zwave_health_json, $output);

		//récupère le contenu du json
		$json = file_get_contents($zwave_health_json);

		//analyse du contenu
		$modules = json_decode($json, true);
		
		$dead_modules = array();
		$timeout_modules = array();
		$ok_modules = array();

		//Idee: parcours des modules avec Tri timeout / Dead et ensuite l'affichage
		foreach ($modules['devices'] as $module){
			//On verifie que le module a des données
			if(isset($module['data']) && $module['data'] != null && $module['data'] != ""){
				//Verification que le module n'est pas sur pile/batterie
				if($module['data']['isListening']['value']){
					if(isset($module['last_notification'])){
						if($module['last_notification'] != null){
							if (utf8_decode($module['last_notification']['description']) == "Dead"){
								$dead_modules[] = utf8_decode($module['data']['description']['location'])." - ".utf8_decode($module['data']['description']['name']);
							}
							else if(utf8_decode($module['last_notification']['description']) == "Timeout"){
								$timeout_modules[] = utf8_decode($module['data']['description']['location'])." - ".utf8_decode($module['data']['description']['name']);
							}
							else{
								$ok_modules[] = utf8_decode($module['data']['description']['location'])." - ".utf8_decode($module['data']['description']['name']);
							}
						}
						
					}
					
				}
				//Traitement des modules piles
				else{
					$checked = false;
					//Modules Piles Timeout				
					if(isset($module['battery_level']['value'])){
						if($module['battery_level']['value'] == null){
							$timeout_modules[] = utf8_decode($module['data']['description']['location'])." - ".utf8_decode($module['data']['description']['name']);
							$checked = true;
						}
					}
					if($checked == false && isset($module['isFailed']['value'])){
						$dead_modules[] = utf8_decode($module['data']['description']['location'])." - ".utf8_decode($module['data']['description']['name']);
						$checked = true;
					}
					if($checked == false){
						$ok_modules[] = utf8_decode($module['data']['description']['location'])." - ".utf8_decode($module['data']['description']['name']);
					}
				}
			}
		}			

		/** HISTORISATION Dead Modules and Timeout **/
		echo "<br />";
		echo sizeof($dead_modules) + sizeof($timeout_modules);
		
		
		/** AFFICHAGE **/
		echo " modules HS: ";
		echo "<br />";
		/** Affichage des modules deads**/
		if($show_option >= 0){
			echo "<div style='color:red; text-decoration : underline;'>";
			echo "Modules Dead: ";
			echo "</div>";
			foreach($dead_modules as $dead_module){
				echo $dead_module;
				echo "<br />";
			}
		}
		echo "<br />";
		
		/** Affichage des modules timeouts **/
		if($show_option >= 1){
			echo "<br />";
			echo "<div style='color:darkorange; text-decoration : underline;'>";
			echo "Modules Timeout: ";
			echo "</div>";
			foreach($timeout_modules as $timeout_module){
				echo $timeout_module;
				echo "<br />";
			}
		}
		echo "<br />";
		
		/** Affichage des module restants **/
		if($show_option >= 2){
			echo "<br />";
			echo "<div style='color:limegreen; text-decoration : underline;'>";
			echo "Modules OK: ";
			echo "</div>";
			foreach($ok_modules as $ok_module){
				echo $ok_module;
				echo "<br />";
			}
		}
		
	}
?>