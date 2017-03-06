<?php 

function aws_query($extraparams) {
    $private_key = '';

    $method = "GET";
    $host = "webservices.amazon.com";
    $uri = "/onca/xml";

    $params = array(
        "AssociateTag" => "asdf08e-20",
        "Service" => "AWSECommerceService",
        "AWSAccessKeyId" => "",
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
    
    echo $string_to_send;

    

}




//$my = array("Operation"=>"ItemLookup", "ItemId"=>"B0017CRZIK", "ResponseGroup"=>"Medium");


function aws_itemlookup($itemId) {
    return aws_query(array (
        "Operation" => "ItemLookup",
        "IdType" => "ASIN",
        "ItemId" => $itemId,
        "ResponseGroup"=>"Medium"
    ));
}


aws_itemlookup('B00KU0EGTG');


 ?>
