<?php
		include 'Excel/PHPExcel.php';
		require_once "scraper/support/http.php";
		require_once "scraper/support/web_browser.php";
		require_once "scraper/support/simple_html_dom.php";

		ini_set('max_execution_time', 0);


		function aws_query($extraparams) {
		    $private_key = '+TRLuYQwpIcVdsJqKnBmvLIw0CE1D+UJtzkxGgKX';

		    $method = "GET";
		    $host = "webservices.amazon.com";
		    $uri = "/onca/xml";

		    $params = array(
		        "AssociateTag" => "asdf08e-20",
		        "Service" => "AWSECommerceService",
		        "AWSAccessKeyId" => "AKIAJNXHGOZUG74NG6QA",
		        "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
		        "SignatureMethod" => "HmacSHA256",
		        "SignatureVersion" => "2",
		       // "Version" => "2016-10-27"
		    );

		    foreach ($extraparams as $param => $value) {
		        $params[$param] = $value;
		    }

		    ksort($params);

		    // sort the parameters
		    // create the canonicalized query
		    $canonicalized_query = array();
		    foreach ($params as $param => $value) {
		        $param = str_replace("%7E", "~", rawurlencode($param));
		        $value = str_replace("%7E", "~", rawurlencode($value));
		        $canonicalized_query[] = $param . "=" . $value;
		    }
		    $canonicalized_query = implode("&", $canonicalized_query);

		    // create the string to sign
		    $string_to_sign =
		        $method . "\n" .
		        $host . "\n" .
		        $uri . "\n" .
		        $canonicalized_query;

		    // calculate HMAC with SHA256 and base64-encoding
		    $signature = base64_encode(
		        hash_hmac("sha256", $string_to_sign, $private_key, True));

		    // encode the signature for the equest
		    $signature = str_replace("%7E", "~", rawurlencode($signature));

		    // Put the signature into the parameters
		    $params["Signature"] = $signature;
		    uksort($params, "strnatcasecmp");

		    // TODO: the timestamp colons get urlencoded by http_build_query
		    //       and then need to be urldecoded to keep AWS happy. Spaces
		    //       get reencoded as %20, as the + encoding doesn't work with 
		    //       AWS
		    $query = urldecode(http_build_query($params));
		    $query = str_replace(' ', '%20', $query);

		    $string_to_send = "https://" . $host . $uri . "?" . $query;

		    $page = get_curl($string_to_send);
  
		    $xml = simplexml_load_string($page);
		    return $xml;

		}

    


		function aws_itemlookup($itemId) {
				return aws_query(array (
			        "Operation" => "ItemLookup",
			        "IdType" => "ASIN",
			        "ItemId" => $itemId,
			        "ResponseGroup"=>"Medium"
			    ));
		}

		function get_curl($url){

		    $ch = curl_init();

		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch,CURLOPT_FAILONERROR,TRUE);
		    curl_setopt($ch, CURLOPT_HEADER, 0);

		    $page = curl_exec($ch);

		    if($page == true){
		    	curl_close($ch);
		    	return $page;
		    	
		    } else {
		    	sleep(2);		    	
		    	return get_curl($url);
		    		
		    }
		}

	


	   // LOAD EXCEL FILE
        function loadFile(){

			$fileName = 'C:/xampp/htdocs/Amazon/Files/proba.xlsx';

			$objPHPExcel = PHPExcel_IOFactory::load($fileName);

			$worksheet = $objPHPExcel->getActiveSheet();

			$highestRow = $worksheet->getHighestRow();
			$highestColumnLetters = $worksheet->getHighestColumn();
			$highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumnLetters); 

			$productArray = array();
			for($i = 2; $i <= $highestRow; $i++){
				if($worksheet->getCellByColumnAndRow(0, $i)->getValue() != null){
					for($j = 0; $j <= $highestColumn; $j++){
						if($worksheet->getCellByColumnAndRow($j, $i)->getValue() != null){
							$element = $worksheet->getCellByColumnAndRow($j, $i)->getValue();
				   			$productArray[$i][$j] = $element;							
						} else {
							break;
						}						
					}
				} else {
					break;
				}
			}
			return $productArray;
		}


	    function getPageAttributes($xml){
	        if(isset($xml->Items->Request->Errors->Error->Message)){
		    	$errorMsg = $xml->Items->Request->Errors->Error->Message;
		    	echo $errorMsg . "\n";	
		    	return FALSE;
		    } else {

		    	//Get Title
		    	$title = $xml->Items->Item->ItemAttributes->Title;
                
		    	//Get Page Description
		    	$description = $xml->Items->Item->EditorialReviews->EditorialReview->Content;

		    	// Get Page Bullets
		    	$bullets = array();
	           	            
				$feature = $xml->Items->Item->ItemAttributes->Feature;
				foreach ($feature as $f) {
					array_push($bullets, $f);
					//echo $f . "\n";
		        }

		        $rank = $xml->Items->Item->SalesRank;


		        $attributes = array("page_title" => $title, "page_description"=>$description, "page_bullets_array"=>$bullets);	     
		        return $attributes;

		    }
	    }

	    function clearDescription($desc){

	    	
         	 $page_no_apostrophy = str_replace('’', '\'', $desc);
         	 $page_no_apostrophy2 = str_replace('”', '"', $page_no_apostrophy);
         	 $page_no_apostrophy3 = str_replace('“', '"', $page_no_apostrophy2);
	         $page_replace_funny_r = str_replace('®', '', $page_no_apostrophy3);
	         $no_emdash = str_replace('—', '-', $page_replace_funny_r);
	         $page_no_emdash2 = str_replace('–', '-', $no_emdash);
	         $page_replace_weird_letter = str_replace('┬', '', $page_no_emdash2);
	         $page_no_tags = strip_tags($page_replace_weird_letter);	
	         $page_replace_trademark = str_replace('TM', '', $page_no_tags);  
	         $page_replace_trademark2 = str_replace('™', '', $page_replace_trademark); 
	      
	         $page_no_blank = preg_replace( "/\r|\n/", " ", $page_replace_trademark2);
	 		 $page_no_space = preg_replace('/\s+/', ' ',$page_no_blank);

	 		 
	 		 
	         

	         $string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $page_no_space);
	         


	      //   $string = preg_replace( '/[^[:print:]]/', '',$page_no_space);
	         $description = trim($string);

	         return $description;

         }
?>






<?php
	         
      

		//Load ASINs

	    $productArray = loadFile();

		$errors_file = new PHPExcel();
		$objWriter = new PHPExcel_Writer_Excel2007($errors_file);	    

	    $row_no = 0;
	    
		foreach($productArray as $row){
		//	echo str_repeat(' ',1024*64);
		//    flush();
			// PAGE

			$ASIN = $row[0];
			
			$xml = aws_itemlookup($ASIN);

			$attributes = getPageAttributes($xml);

			if($attributes == FALSE){
				$row_no++;
				$errors_file->getActiveSheet()->getCell('A'. $row_no)->setValue($ASIN); 
			//	echo "<tr><td class='error' >" . $ASIN . "</td>";
			} else {

				$page_title = $attributes['page_title'];
				$page_desc = $attributes['page_description'];
				$page_bullets = $attributes['page_bullets_array'];

				$clear_page_bullets = [];
				foreach($page_bullets as $bullets){
					$bullet = clearDescription($bullets);
					array_push($clear_page_bullets, $bullet);
				}	

				$excel_title = $row[1];
				$excel_desc = $row[2];
				$excel_bullets = array();
				for($i = 3; $i < count($row); $i++){
					array_push($excel_bullets, $row[$i]);
				}

				$clear_excel_bullets = [];
				foreach($excel_bullets as $bullets){
					$bullet = clearDescription($bullets);
					array_push($clear_excel_bullets, $bullet);
				}	

				
				$page_description = clearDescription($page_desc);
				$excel_description = clearDescription($excel_desc);
		//		$differences = array_diff($excel_bullets, $page_bullets);

				

				$row_no++;
				echo "ASIN " . $ASIN . "\n";
				$errors_file->getActiveSheet()->getCell('A'. $row_no)->setValue($ASIN);         
			//	echo "<tr><td>" . $ASIN . "</td>";

				if($page_title != $excel_title){
				//	echo "<td class='error'>Error</td>";
					echo "Title is different." . "\n";
					var_dump($excel_title);
					var_dump($page_title);
					exec('pause');
				} else {
				//	echo "<td class='match'>Match</td>";
				}

				if($page_description != $excel_description){
				//	echo "<td class='error'>Error</td>";
					echo "Description is different." . "\n";
					var_dump($excel_description);
					var_dump($page_description);
					exec('pause');
				} else {
				//	echo "<td class='match'>Match</td>";
				}
	/*
				if(!empty($differences)){
					echo "<td class='error'>Error</td>";
				//	echo "Bullets are different." . "\n";
				//	print_r($excel_bullets);
				//	print_r($page_bullets);
				//	exec('pause');
				} else {
					echo "<td class='match'>Match</td>";
				}
	*/

				foreach ($clear_excel_bullets as $bullet) {
					if(in_array($bullet, $clear_page_bullets)){
						echo "Good Bullet" . "\n";
					//	echo "<td class='match'>Match</td>";
					} else {
						echo "Bad Bullet" . "\n";
						var_dump($bullet);
						var_dump($page_bullets);
						exec('pause');
					//	echo "<td class='error'>Error</td>";
					}
				}
				
				$excel_bullets_count = count($excel_bullets);
				$page_bullets_count = count($page_bullets);

				while( $page_bullets_count > $excel_bullets_count){
				//	echo "<td class='error'>Error</td>";
					echo "Additinal bullet on page.";
					$excel_bullets_count++;
				}	

				while($page_bullets_count < 5){
				//	echo "<td class='match'>Match</td>";
					$page_bullets_count++;
				}			
				
			//	echo "</tr>";

				if(count($page_bullets) > count($excel_bullets)){
				//	echo "There are additional bullets on the listing" . "\n";
				}
	
		}
			$objWriter->save("C:/xampp/htdocs/Amazon/Files/errors.xlsx");
	}
	// END OF FOREACH





?>				
			</table>
		</div>
	</div>
</body>
</html>

























