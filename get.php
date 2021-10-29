<?

$data = json_decode(file_get_contents('test.json'), true);

$pricebreaks = array();
$products=array();


foreach($data['Products'] as $p) {
    print var_dump(isset($p['StandardPricing'])); print var_dump(isset($p['Packaging']));
    if(isset($p['StandardPricing']) && isset($p['Packaging']) ) {
	$products[$p['DigiKeyPartNumber']] = array('prices' => array(), 'packaging' => $p['Packaging']['Value']);
	foreach($p['StandardPricing'] as $s) {
	    $products[$p['DigiKeyPartNumber']]['prices'][$s['BreakQuantity']] = $s['UnitPrice'];
	}
    }
} 
print_r($products);
if(is_array($data['ExactManufacturerProducts']) && count($data['ExactManufacturerProducts'])) {
    $pr = $data['ExactManufacturerProducts'][0];
    $pricing = array($products[$pr['DigiKeyPartNumber']]);
    if(is_array($pr['AlternatePackaging']) && count($pr['AlternatePackaging']) > 0) {
	foreach($pr['AlternatePackaging'] as $ap) {
	    $pricing[]=$products[$ap['DigiKeyPartNumber']];
	}
    }
    print_r($pricing);
    
}


?>