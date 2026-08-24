<?php
// A deliberately small, demo-friendly set of Lebanese districts.
function lebanon_regions(){
    return ["Beirut", "Metn", "Keserwan", "Baabda", "Aley", "Chouf", "Tripoli", "Zahle", "Saida", "Tyre"];
}

function nearby_regions($region){
    $groups = [
        "Beirut" => ["Beirut", "Metn", "Baabda", "Aley"],
        "Metn" => ["Metn", "Beirut", "Keserwan", "Baabda"],
        "Keserwan" => ["Keserwan", "Metn", "Beirut"],
        "Baabda" => ["Baabda", "Beirut", "Metn", "Aley"],
        "Aley" => ["Aley", "Baabda", "Beirut", "Chouf"],
        "Chouf" => ["Chouf", "Aley", "Saida"],
        "Tripoli" => ["Tripoli"],
        "Zahle" => ["Zahle"],
        "Saida" => ["Saida", "Chouf", "Tyre"],
        "Tyre" => ["Tyre", "Saida"]
    ];
    return isset($groups[$region]) ? $groups[$region] : [];
}
?>
