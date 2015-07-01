<?php
	/**
	** @author Diego Cardona < cardona.root@gmail.com >
	**/

	$referer = ""; //url home facebook user that will be named like referer

//Logs
	$sql_logs = fopen( __DIR__.'/logs_sql.txt', 'c+');

//Database
	$servername = "localhost";
	$username = "root";
	$password = "password";

	try {
		fwrite( $sql_logs, date("Y-m-d H:i:s") . ": Starting DB Connection\n" );
		$conn = new PDO("mysql:host=$servername;dbname=facebook_ids", $username, $password);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		fwrite( $sql_logs, date("Y-m-d H:i:s") . ": Connection Succesful\n" );
	}
	catch(PDOException $e){
		fwrite( $sql_logs, date("Y-m-d H:i:s") . ": Connection failed: " . $e->getMessage() . "\n" );
	}

	//Consulta de urls para revisar
	$urls_pendientes = array(
		array('id' => $referer )
	);

	do{

		foreach ($urls_pendientes as $key => $url) {
			
			//Curl
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url['id']);
			curl_setopt($ch,CURLOPT_ENCODING , "");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			//Facebook headers
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
				'accept-encoding: gzip, deflate, sdch',
				'accept-language: en-US,en;q=0.8,es;q=0.6',
				'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.89 Safari/537.36',
				'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'referer: ' . $referer,
				'cookie: datr=XPoAVbMUJg7gsFiCYberI51a; lu=Tg2xrkq0WQJmyoLSnX9JggIg; a11y=%7B%22sr%22%3A0%2C%22sr-ts%22%3A1429751817452%2C%22jk%22%3A1%2C%22jk-ts%22%3A1430777954745%2C%22kb%22%3A0%2C%22kb-ts%22%3A1430190177661%2C%22hcm%22%3A0%2C%22hcm-ts%22%3A1429751817452%7D; c_user=796961896; fr=0PsCuOECfQDVQxsLa.AWWLbyUtZSD2pdEwlKAar8299ko.BVAPrP.o8.FVC.0.AWUAGUGp; xs=156%3A7OScJm70bT0fxw%3A2%3A1427126359%3A7271; csm=2; s=Aa6TuezqoUOIQy0t.BVEDhX; p=-2; presence=EM430780760EuserFA2796961896A2EstateFDsb2F1430692054650Et2F_5bDiFA2user_3a1B01108595580A2ErF1C_5dElm2FnullEuct2F1430691975755EtrFA2loadA2EtwF2992424567EatF1430780760325G430780760661CEchFDp_5f796961896F3CC; wd=1366x396; act=1430780778885%2F9'
			) );
			
			$html = curl_exec($ch); 

			preg_match_all('/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/', trim($html), $urls);
			preg_match_all('/data-hovercard="\/ajax\/hovercard\/user.php\?id=([^"]+)&amp*/', trim($html), $fb_ids);

			try{
				$query = $conn->prepare('UPDATE urls SET checked = 1 WHERE id ="' . $url['id'] . '";');
				$query->execute();
			}
			catch(PDOException $e){
				fwrite( $sql_logs, date("Y-m-d H:i:s") . ": SQL Exception: " . $e->getMessage() . "\n" );
			}

			foreach ($fb_ids[1] as $key => $fb_id) {
				try{
					$query = $conn->prepare('INSERT INTO collection(id,checked) VALUES ( '. $fb_id .', "0" );');
					$query->execute();
					echo $fb_id . "\n";
					fwrite( $sql_logs, date("Y-m-d H:i:s") . ": SQL Insert OK: " . $fb_id . "\n" );
				}
				catch(PDOException $e){
				}
			}
			
			foreach ($urls[1] as $key => $url) {
				try{
					if( (substr($url, 0, 4) == 'http') 
						AND (strpos($url,'facebook') !== FALSE)
						AND (strpos($url,'developers.') === FALSE) 
						AND (strpos($url,'code.') === FALSE)
 						AND (strpos($url,'l.facebook') === FALSE) ){
						$query = $conn->prepare('INSERT INTO urls(id,checked) VALUES ( "'. $url .'", 0 );');
						$query->execute();
						fwrite( $sql_logs, date("Y-m-d H:i:s") . ": SQL Insert OK: " . $url . "\n" );
					}
				}
				catch(PDOException $e){
				}
			}
		}

		//Validación de urls
		try{
			$query = $conn->query('SELECT id FROM urls WHERE checked = 0;');
			$urls_pendientes = $query->fetchAll();
			fwrite( $sql_logs, date("Y-m-d H:i:s") . ": SQL QUERY OK, SELECT id FROM urls WHERE checked = 0;\n" );
		}
		catch(PDOException $e){
			fwrite( $sql_logs, date("Y-m-d H:i:s") . ": SQL Exception: " . $e->getMessage() . "\n" );
		}

	}while( $urls_pendientes != array() );
