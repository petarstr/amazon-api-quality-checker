  <!DOCTYPE html>
<html>
	<head>
		<title>Listing Checker</title>
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<!-- jQuery library -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<!-- Latest compiled JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

		<style>
		.progress {
			height: 30px;
		}
		.row {
			margin-top: 15%;
		}
		.buttons {
			margin-top: 5%;
		}
		#download-btn {
			margin-left: 5%;
		}
		</style>

	</head>
	
	<body>

		<div class='row'>

			<div class="col-sm-4"></div>

			<div class="col-sm-4">
				 <div class="notice" align="center"> <h2>In progress..</h2> </div>
				 <div class="progress">	</div>

				 <div class="buttons" align='center'>

				 	<button id='another-file' type='submit' onclick="window.open('http://localhost/Amazon/index.php')" class='btn btn-default btn-lg' >Check another file
				 	</button>

				 	<button id='download-btn' type='submit' onclick="window.open('C:/xampp/htdocs/Amazon/Files/errors.xlsx')" class='btn btn-info btn-lg' disabled>Download
				 	</button>

				</div>
			</div>
		</div>
		
	</body>
</html>



<?php ini_set('max_execution_time', 0);
		include 'Excel/PHPExcel.php';
		require_once "scraper/support/http.php";
		require_once "scraper/support/web_browser.php";
		require_once "scraper/support/simple_html_dom.php";


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
	    
		    
	    	$page = @file_get_contents($string_to_send);

	    	if($page !== false){
	    		$xml = simplexml_load_string($page);
	    	} else $xml = null;

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




	   // LOAD EXCEL FILE
        function loadFile(){

			$fileName = 'C:/xampp/htdocs/Amazon/uploads/pera.xlsx';

			$objPHPExcel = PHPExcel_IOFactory::load($fileName);

			$worksheet = $objPHPExcel->getActiveSheet();

			$highestRow = $worksheet->getHighestRow();
			$highestColumnLetters = $worksheet->getHighestColumn();
			$highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumnLetters);

		//	$multidimensionalArray = array();
			$productArray = array();

			for($i = 1; $i <= $highestRow; $i++){
				for($j = 0; $j <= $highestColumn; $j++){
					$check = $worksheet->getCellByColumnAndRow('A', $i)->getValue();
					if($check != ""){
						$element = $worksheet->getCellByColumnAndRow($j, $i)->getValue();					
						$productArray[$i][$j] = $element;						
					}
				}
			}
			
			return $productArray;
		}


	    function getPageAttributes($xml){
	        if(isset($xml->Items->Request->Errors->Error->Message)){
		    	$errorMsg = $xml->Items->Request->Errors->Error->Message;
		    	$attributes = null;
		    //	echo $errorMsg . "\n";	
		    } else {

		    	//Get Title
		    	$title = $xml->Items->Item->ItemAttributes->Title;
                
		    	//Get Page Description
		    	$description = $xml->Items->Item->EditorialReviews->EditorialReview->Content;
		    	$bullets = array();
	            
	            //Get Page Bullets
				$feature = $xml->Items->Item->ItemAttributes->Feature;
				foreach ($feature as $f) {
					array_push($bullets, $f);
					//echo $f . "\n";
		        }

		        $rank = $xml->Items->Item->SalesRank;

		        $attributes = array("page_title" => $title, "page_description"=>$description, "page_bullets_array"=>$bullets);	     
		        

		    }
		    return $attributes;
	    }

	    function clearText($desc){
         	 $page_no_apostrophy = str_replace('’', '\'', $desc);
	         $page_replace_funny_r = str_replace('®', ' ', $page_no_apostrophy);
	         $no_emdash = str_replace('—', '-', $page_replace_funny_r);
	         $page_no_emdash2 = str_replace('–', '-', $no_emdash);
	         $page_replace_weird_letter = str_replace('┬', '', $page_no_emdash2);
	         $page_no_tags = strip_tags($page_replace_weird_letter);	
	         $page_replace_trademark = str_replace('TM', '', $page_no_tags);  
	         $page_replace_trademark2 = str_replace('™', '', $page_replace_trademark); 
	         $escape_dots = str_replace('.', '', $page_replace_trademark2); 
	      
	         $page_no_blank = preg_replace( "/\r|\n/", " ", $escape_dots);
	 		 $page_no_space = preg_replace('/\s+/', ' ',$page_no_blank);
	 		 
	         $description =  $page_no_space;

	         return $description;

         }

         function connectionHandle($row){
         	$ASIN = $row[0];
        	$xml = aws_itemlookup($ASIN);

        	if($xml != null){
	         //	echo $ASIN;
	         	$attributes = getPageAttributes($xml);

	         	compare($attributes, $row);
        	} else {
        		connectionHandle($row);
        	}
         }

        function compare($attributes, $row){
        	GLOBAL $objPHPExcel1, $row_no;
        	$ASIN = $row[0];

			 $page_title = $attributes['page_title'];
	         $page_desc = $attributes['page_description'];
	         $page_bullets = $attributes['page_bullets_array'];		         		
	         
	         // Get all Excel info
	   		 $excel_desc = $row[2];
	   		 $excel_title = $row[1];
	   		 $excel_bullets_array = array();

			 for($bullet = 3; $bullet <= 7; $bullet++){
				if($row[$bullet] != ""){
					$element = clearText($row[$bullet]);
					array_push($excel_bullets_array, $element);
				}
			 }

	        //Remove inadequate symbols
			$page_title = clearText($page_title);
	 		$excel_title = clearText($excel_title);

	        $page_description = clearText($page_desc);
	        $excel_description = clearText($excel_desc);

	        $page_bullets_array = array();
	        if($page_bullets){
		        foreach($page_bullets as $page_bullet){
		        	$page_bullet = clearText($page_bullet);
		        	array_push($page_bullets_array, $page_bullet);
		        }	        	
	        }

		//	$excel_bullets_array = clearText($excel_bullets_array);
			$different_bullets_excel = array_diff($excel_bullets_array, $page_bullets_array);

			if($attributes == null){
				$row_no++;
				$objPHPExcel1->getActiveSheet()->getCell('A'.$row_no)->setValue($ASIN . " ASIN is not available");				
			} else {

				if($page_description == $excel_description && $page_title == $excel_title && empty($different_bullets_excel)){
					//echo "Sve isto" . "\n";
				} else {
					$row_no++;
					$objPHPExcel1->getActiveSheet()->getCell('A'.$row_no)->setValue($ASIN);
				}

				if($page_description != $excel_description){
					$objPHPExcel1->getActiveSheet()->getCell('C'.$row_no)->setValue($excel_description);
				}

				if($page_title != $excel_title){
					$objPHPExcel1->getActiveSheet()->getCell('B'.$row_no)->setValue($excel_title);
				}

				if(!empty($different_bullets_excel)){
					$col = 3;
					for($b = 0; $b < count($different_bullets_excel); $b++){
						$objPHPExcel1->getActiveSheet()->getCellByColumnAndRow($col, $row_no)->setValue($different_bullets_excel[$b]);
						$col++;
					}
				}
			}
        } 

         //Prepare new spreadsheet for errors
     	$objPHPExcel1 = new PHPExcel();
     	$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel1);

		//Load ASINs
	    $productArray = loadFile();
        $current = 0;
        $row_no = 0;

		foreach($productArray as $row){
			
	        $total = count($productArray);
	        $current++;
	        $show = round($current/$total * 100, 0) . "%";
	
	        connectionHandle($row);

		    echo str_repeat(' ',1024*64);
		    flush();
		

		    echo"
					<script language='javascript'>
		     		document.getElementsByClassName('progress')[0].innerHTML = '<div class=\'progress-bar progress-bar-striped active\' aria-valuenow=\'70\' aria-valuemin=\'0\' aria-valuemax=\'50\' style=\'width:".$show."\'></div>';
    				</script>";
 
			}
	// END OF FOREACH

		    echo"
					<script language='javascript'>
					document.getElementsByClassName('notice')[0].innerHTML = '<h2> Your file is ready </h2>';
		     		document.getElementById('download-btn').disabled = false;
    				</script>";



	//When everything is done save the file
	$objWriter->save("C:/xampp/htdocs/Amazon/Files/errors.xlsx");

 ?>







