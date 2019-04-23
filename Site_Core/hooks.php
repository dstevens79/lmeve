<?php

include_once('../Modules/Database/dbcatalog.php');

function login_hook() {
    updateUserstable();
    recreateSdeCompatViews();
    updateCrestIndustrySystems();
    createCitadelsView();
    esiUpdateAll();
    updateApiAssets();
    decryptorTables();
}

?>