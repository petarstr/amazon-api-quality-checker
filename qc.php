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


		function getFile(){
			if(isset($_FILES['file'])){
	            $file = $_FILES['file'];

	            $file_name = $file['name'];
	            $file_tmp = $file['tmp_name'];
	            $file_error = $file['error'];

	            $file_ext = explode('.', $file_name);
	            $file_ext = strtolower(end($file_ext));

	            $allowed_ext = 'xlsx';

	            if($file_ext == $allowed_ext){
	                if($file_error === 0){
	                    $file_name_new = uniqid('', true) . '.' . $file_ext;
	                    $file_destination = 'uploads/' . $file_name_new;
	                    if(move_uploaded_file($file_tmp, $file_destination)){
	                        echo str_repeat(' ',1024*64);
	                        flush(); 
	                    }
	                }
	            }
        	}

        	return $file_destination;
		}

	


	   // LOAD EXCEL FILE
        function loadFile(){

        	$file_destination = getFile();

			$fileName = $file_destination;

			$objPHPExcel = PHPExcel_IOFactory::load($fileName);

			$worksheet = $objPHPExcel->getActiveSheet();

			$highestRow = $worksheet->getHighestRow();
			$highestColumnLetters = $worksheet->getHighestColumn();
			$highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumnLetters); 

			$productArray = array();
			for($i = 2; $i <= $highestRow; $i++){
				//Check if ASIN is available
				if($worksheet->getCellByColumnAndRow(0, $i)->getValue() != null){
					for($j = 0; $j <= 8; $j++){
						if($worksheet->getCellByColumnAndRow($j, $i)->getValue() != null){
							$element = $worksheet->getCellByColumnAndRow($j, $i)->getValue();
				   			$productArray[$i][$j] = $element;							
						} else {
							continue;
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
		    	return FALSE;
		    } else {

		    	//Get Title
		    	if($xml->Items->Item->ItemAttributes->Title){
		    		$title = $xml->Items->Item->ItemAttributes->Title;
		    	} else {
		    		$title = "N/A";
		    	}
		    	
                
		    	//Get Page Description
		    	if($xml->Items->Item->EditorialReviews){
		    		$description = $xml->Items->Item->EditorialReviews->EditorialReview->Content;
		    	} else {
		    		$description = "N/A";
		    	}
		    	

		    	// Get Page Bullets
		    	$bullets = array();

		    	if($xml->Items->Item->ItemAttributes->Feature){
					$feature = $xml->Items->Item->ItemAttributes->Feature;
					foreach ($feature as $f) {
						array_push($bullets, $f);
			        }		    		
		    	}
	           	            
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
	         $description = trim($string);

	         return $description;

         }
?>



<!DOCTYPE html>
<html>
<head>
	<title>QualityChecker</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

	<!-- Latest compiled JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<link rel="stylesheet" type="text/css" href="style.css">

</head>
<body>

	<div class="container">
		<div class="content">
			<h2>Quality Check</h2>
			<div class="dwnld">
				<button class="btn btn-info btn-sm">Download</button>
			</div>
			
			<table class="table table-striped table-bordered"">
				<tr>
					<th>ASIN</th>
					<th>Title</th>
					<th>Description</th>
					<th>Bullet 1</th>
					<th>Bullet 2</th>
					<th>Bullet 3</th>
					<th>Bullet 4</th>
					<th>Bullet 5</th>
				</tr>


<?php
		if(isset($_FILES['file'])){
			run();
		} else {
			header("Location: index.php");
		}
	         
        function run(){
			//Load ASINs

		    $productArray = loadFile();

			$errors_file = new PHPExcel();
			$objWriter = new PHPExcel_Writer_Excel2007($errors_file);	    

		    $row_no = 0;
		    
			foreach($productArray as $row){
				echo str_repeat(' ',1024*64);
			    flush();

				// PAGE
				$ASIN = $row[0];
				
				$xml = aws_itemlookup($ASIN);

				$attributes = getPageAttributes($xml);

				//If ASIN is not valid
				if($attributes == FALSE){
					$row_no++;
					$errors_file->getActiveSheet()->getCell('A'. $row_no)->setValue($ASIN); 
					echo "<tr><td class='error' >" . $ASIN . "</td>";
				} else {

					$page_title = $attributes['page_title'];
					$page_title = clearDescription($page_title);

					$page_desc = $attributes['page_description'];
					$page_bullets = $attributes['page_bullets_array'];	

					$clear_page_bullets = [];
					foreach($page_bullets as $bullets){
						$bullet = clearDescription($bullets);
						array_push($clear_page_bullets, $bullet);
					}	

					$excel_title = $row[1];
					$excel_title = clearDescription($excel_title);
					
					$excel_desc = $row[2];

					$excel_bullets = array();
					//Check which is the last key to be used for extracting bullets
				    end($row);
					$last_key = key($row);				
					reset($row);
							
					for($i = 3; $i <= $last_key; $i++){
						if(isset($row[$i])){
							array_push($excel_bullets, $row[$i]);
						} else {
							continue;
						}
					}
					
					$clear_excel_bullets = [];
					foreach($excel_bullets as $bullets){
						$bullet = clearDescription($bullets);
						array_push($clear_excel_bullets, $bullet);
					}	

					
					$page_description = clearDescription($page_desc);
					$excel_description = clearDescription($excel_desc);
	
					$row_no++;
					         
					echo "<tr><td>" . $ASIN . "</td>";

					if($page_title != $excel_title){
						echo "<td class='error'>Error</td>";
						$errors_file->getActiveSheet()->getCell('A'. $row_no)->setValue($ASIN);
					//	echo "Title is different." . "\n";
					//	var_dump($excel_title);
					//	var_dump($page_title);
					//	exec('pause');
					} else {		
						echo "<td class='match'>Match</td>";
					}

					if($page_description != $excel_description){
						echo "<td class='error'>Error</td>";
						$errors_file->getActiveSheet()->getCell('A'. $row_no)->setValue($ASIN);
					//	echo "Description is different." . "\n";
					//	var_dump($excel_description);
					//	var_dump($page_description);
					//	exec('pause');
					} else {
						echo "<td class='match'>Match</td>";
						
					}

					foreach ($clear_excel_bullets as $bullet) {
						if(in_array($bullet, $clear_page_bullets)){
							echo "<td class='match'>Match</td>";
						} else {
							echo "<td class='error'>Error</td>";
							$errors_file->getActiveSheet()->getCell('A'. $row_no)->setValue($ASIN);
						}
					}
					
					$excel_bullets_count = count($clear_excel_bullets);
					$page_bullets_count = count($clear_page_bullets);

					while( $page_bullets_count > $excel_bullets_count){
						echo "<td class='error'>Error</td>";
						$errors_file->getActiveSheet()->getCell('A'. $row_no)->setValue($ASIN);
						$excel_bullets_count++;
					}	

					while($excel_bullets_count < 5){
						echo "<td class='match'>Match</td>";
						$excel_bullets_count++;
					}	

					echo "</tr>";
			}
				$objWriter->save("C:/xampp/htdocs/Amazon/Files/errors.xlsx");
		}
		// END OF FOREACH	
      }


?>				
			</table>
		</div>
	</div>
</body>
</html>

























