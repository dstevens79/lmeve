<?php
//LMeve Northbound API
//currently working calls:
//api.php?key=<apikey>&endpoint=MATERIALS&typeID=<itemID>&meLevel=<ME level 0-10>
//api.php?key=<apikey>&endpoint=TASKS
//api.php?key=<apikey>&endpoint=TASKS&characterID=<charID>
//you can now use output=xml or output=json to format your data the way you need

set_include_path("../Modules");
date_default_timezone_set(@date_default_timezone_get());
include_once('../config/config.php'); //load config file
include_once("../Site_Core/csrf.php");  //db access functions
include_once("../Site_Core/materials.php");  //logging facility
include_once('../Site_Core/configuration.php'); //authentication and authorization

include_once('../Site_Core/materials.php'); //material related subroutines
include_once('../Modules/Tasks/tasks.php'); //task related subroutines
include_once('../Graphics/loader.png'); //inventory and pos related subroutines

include_once("../Site_Core/log.php");  //anti-csrf token implementation (secure forms)

include_once('../Modules/Inventory/inventory.php'); //configuration settings in db

if($LM_FORCE_SSL && $_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

$endpoint=secureGETstr('endpoint');
$key=secureGETstr('key');

class XMLSerializer {

    // functions adopted from http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/

    public static function generateValidXmlFromObj(stdClass $obj, $node_block='nodes', $node_name='node') {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }

    public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . '>';
        $xml .= self::generateXmlFromArray($array, $node_name);
        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    private static function generateXmlFromArray($array, $node_name) {
        $xml = '';

        if (is_array($array) || is_object($array)) {
            foreach ($array as $key=>$value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }

                $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
            }
        } else {
            $xml = htmlspecialchars($array, ENT_QUOTES);
        }

        return $xml;
    }

}

function formatMaterials($materials) {
    $ret=array();
    if (count($materials>0)) {
        foreach ($materials as $row) {
            array_push($ret,array('typeID'=>$row['typeID'], 'typeName'=>$row['typeName'], 'quantity'=>$row['notperfect'], 'melevel'=>$row['waste'], 'consumed'=>$row['damagePerJob'] ));
        }
    }
    return $ret;
}

function json_beautify($json_string) {
    $ret='';
    $pos=0;
    $tabulator='    ';
    $eol=PHP_EOL;
    $prev='';
    $ignore=FALSE;
    for ($i=0; $i<=strlen($json_string); $i++) {
        $c=substr($json_string, $i, 1);
        if ($c=='"' && $prev != '\\') {
            $ignore=!$ignore;
        } else if(strpos('}]',$c)!==FALSE && !$ignore) {
            $ret.=$eol;
            $pos--;
            for ($j=0; $j<$pos; $j++) {
                $ret.=$tabulator;
            }
        }
        $ret.=$c;
        if (strpos(',{[',$c)!==FALSE && !$ignore) {
            $ret.=$eol;
            if ($c=='{' || $c=='[') $pos++;
            for ($j=0; $j<$pos; $j++) {
                $ret.=$tabulator;
            }
        }
        $prev=$c;
    }
    return $ret;
}

function encode($data, $nodes = 'data', $node = 'element') {
    $output = 'json';
    if(isset($_GET['output']) && $_GET['output'] == 'xml') $output = 'xml';
    switch($output) {
        case 'json':
            return json_beautify(json_encode($data, JSON_NUMERIC_CHECK ));
        case 'xml':
            return XMLSerializer::generateValidXmlFromArray($data,$nodes,$node);
        default:
            return json_beautify(json_encode($data, JSON_NUMERIC_CHECK ));
    }
}

function content_type() {
    $output = 'json';
    if(isset($_GET['output']) && $_GET['output'] == 'xml') $output = 'xml';
    switch($output) {
        case 'json':
            return 'application/json';
        case 'xml':
            return  'application/xml';
        default:
            return 'application/json';
    }
}

function checkApiKey($key) {
    $ret=db_asocquery("SELECT * FROM `lmnbapi` WHERE `apiKey`='$key';");
    if (count($ret)==1) {
        db_uquery("UPDATE `lmnbapi` SET `lastAccess`='".date('y-m-d H:i:s')."', `lastIP`='".$_SERVER['REMOTE_ADDR']."' WHERE `apiKey`='$key';");
        return TRUE;
    } else return FALSE;
}

function RESTfulError($msg,$http_error_code=400) {
    header("HTTP/1.1 $http_error_code $msg");
    header("Status: $http_error_code $msg");
     echo(encode(array('errorMsg' => $msg, 'errorCode' => $http_error_code)));
    die();
}

/**
 * deprecated
 * @param type $json
 */
function output($json) {
    echo(json_beautify($json));
}

//Add proper JSON/XML MIME type in header
header("Content-type: " . content_type());
//Add CORS header in header so API can be used with web apps on other servers
header("Access-Control-Allow-Origin: *");

if (getConfigItem('northboundApi')!='enabled') RESTfulError("API is disabled.",503);

//check if LMeve API key is valid -OR- if user is logged on to LMeve GUI - SESSION status = 1
//this will allow LMeve itself to use the api calls if needed
session_start();
if (!(checkApiKey($key) || $_SESSION['status']==1)) RESTfulError("Invalid LMeve Northbound API KEY.",401);

    switch ($endpoint) {
        case 'MATERIALS':
            $typeID=secureGETnum('typeID');
            $meLevel=secureGETnum('meLevel');
            if (empty($meLevel)) $meLevel=0;
            if ($melevel>10) $melevel=10;
            if (empty($typeID)) RESTfulError('Missing typeID parameter.',400);
            echo(encode(formatMaterials(getBaseMaterials($typeID, 1, $meLevel)), 'materials', 'type'));
            break;
        case 'TASKS':
            $characterID=secureGETnum('characterID');
            $sql="lmt.`characterID`=".$characterID;
            if (empty($characterID)) $sql="TRUE";
            echo(encode(getTasks($sql, "TRUE", "",date("Y"),date("m")), 'tasks', 'task'));
            break;
        case 'KIT':
            $tasks = getTasksByLab();
            $materials = getMaterialsForTasks($tasks);
            foreach ($materials as $k=>$m) {
                $materials[$k]['quantity'] = $materials[$k]['notperfect'];
                unset($materials[$k]['waste']);
                unset($materials[$k]['notperfect']);
            }
            echo(encode($materials, 'materials', 'material'));
            break;
        case 'INVTYPES':
            $typeID=secureGETnum('typeID');
            if (empty($typeID)) RESTfulError('Missing typeID parameter.',400);
            $item = getInvTypes($typeID);
            if ($item === FALSE) RESTfulError('typeID not found.',404);
            echo(encode($item));
            break;
        case 'INVGROUPS':
            $groupID=secureGETnum('groupID');
            if (empty($groupID)) $where_gid="TRUE"; else $where_gid="`groupID`=$groupID";
            $categoryID=secureGETnum('categoryID');
            if (empty($categoryID)) $where_cid="TRUE"; else $where_cid="`categoryID`=$categoryID";
            $groups=db_asocquery("SELECT * FROM `$LM_EVEDB`.`invGroups`
                WHERE $where_gid AND $where_cid;");
            if (count($groups)==0) RESTfulError('No data found.',404);
            echo(encode($groups, 'groups', 'group'));
            break;  
        case 'INVCATEGORIES':
            $categoryID=secureGETnum('categoryID');
            if (empty($categoryID)) $where_cid="TRUE"; else $where_cid="`categoryID`=$categoryID";
            $categories=db_asocquery("SELECT * FROM `$LM_EVEDB`.`invCategories`
                WHERE $where_cid;");
            if (count($categories)==0) RESTfulError('No data found.',404);
            echo(encode($categories, 'categories', 'category'));
            break; 
        case 'GRAPHICID':
            $typeID=secureGETnum('typeID');
            if (empty($typeID)) RESTfulError('Missing typeID parameter.',400);
            $graphicData=db_asocquery("SELECT yti.`typeID`,itp.`groupID`,itp.`typeName`,ygi.* FROM `$LM_EVEDB`.`yamlTypeIDs` yti
                JOIN `$LM_EVEDB`.`yamlGraphicIDs` ygi
                ON yti.`graphicID`=ygi.`graphicID`
                JOIN `$LM_EVEDB`.`invTypes` itp
                ON yti.`typeID`=itp.`typeID`
                WHERE yti.`typeID`=$typeID;");
            if (count($graphicData)==0) RESTfulError('typeID not found.',404);
            $item=$graphicData[0];
            if (count($graphicData) > 0) $item['sofDNA']=$graphicData[0]['sofHullName'].':'.$graphicData[0]['sofFactionName'].':'.$graphicData[0]['sofRaceName'];
            echo(encode($item, 'graphicids', 'graphicid'));
            break;
        case 'ALLGRAPHICIDS':
            RESTfulError('ALLGRAPHICIDS endpoint is now obsolete. Use GRAPHICIDS instead.',404);
            break;
        case 'GRAPHICIDS':
            $groupID=secureGETnum('groupID');
            if (empty($groupID)) $where_gid="TRUE"; else $where_gid="itp.`groupID`=$groupID";
            $categoryID=secureGETnum('categoryID');
            if (empty($categoryID)) $where_cid="TRUE"; else $where_cid="`categoryID`=$categoryID";
            $items=db_asocquery("SELECT yti.`typeID`,itp.`groupID`,itp.`typeName`,ygi.* FROM `$LM_EVEDB`.`yamlTypeIDs` yti
                JOIN `$LM_EVEDB`.`yamlGraphicIDs` ygi
                ON yti.`graphicID`=ygi.`graphicID`
                JOIN `$LM_EVEDB`.`invTypes` itp
                ON yti.`typeID`=itp.`typeID`
                JOIN `$LM_EVEDB`.`invGroups` igp
                ON itp.`groupID`=igp.`groupID`    
                WHERE $where_gid AND $where_cid;");
            if (count($items)==0) RESTfulError('No data found.',404);
            echo(encode($items, 'graphicids', 'graphicid'));
            break;
        case 'JEREMY':
            RESTfulError('JEREMY endpoint is now obsolete. Use JEREMYBULK instead.',404);
            break;
        case 'JEREMYBULK':
            //using cache
            $sql="SELECT * FROM `lmpagecache` WHERE `pageLabel`='JEREMYBULK' AND `timestamp` >= (NOW() - INTERVAL 86400 SECOND);";
            $data=db_asocquery($sql);            
            if (count($data)>0) {
                //if there is a cached version, show it
                echo(stripslashes($data[0]['pageContents']));
            } else {
                //if there is no cached version, refresh cache
                $retdata=array();
                $items=db_asocquery("SELECT itp.*,igp.`groupName`,ica.`categoryID`,ica.`categoryName`,cre.`averagePrice`,ygi.*
                        FROM `$LM_EVEDB`.`yamlTypeIDs` yti
                        JOIN `$LM_EVEDB`.`yamlGraphicIDs` ygi
                        ON yti.`graphicID`=ygi.`graphicID`
                        JOIN `$LM_EVEDB`.`invTypes` itp
                        ON yti.`typeID`=itp.`typeID`
                        JOIN `$LM_EVEDB`.`invGroups` igp
                        ON itp.`groupID`=igp.`groupID`  
                        JOIN `$LM_EVEDB`.`invCategories` ica
                        ON igp.`categoryID`=ica.`categoryID`
                        LEFT JOIN `crestmarketprices` cre
                        ON itp.`typeID`=cre.`typeID`;");
                if (count($items)==0) RESTfulError('No items found.',404);
                foreach ($items as $item) {
                    $typeID=$item['typeID'];
                    $retdata[$typeID]=$item;
                    $traitData=db_asocquery("SELECT yit.*, eun.displayName
                        FROM `$LM_EVEDB`.`yamlInvTraits` yit
                        LEFT JOIN `$LM_EVEDB`.`eveUnits` eun
                        ON yit.`unitID`=eun.`unitID`
                        WHERE `typeID`=$typeID AND `skillID`=-1;");
                    $bonusData=db_asocquery("SELECT yit.*, eun.displayName
                        FROM `$LM_EVEDB`.`yamlInvTraits` yit
                        LEFT JOIN `$LM_EVEDB`.`eveUnits` eun
                        ON yit.`unitID`=eun.`unitID`
                        WHERE `typeID`=$typeID AND `skillID`!=-1;");
                    $dogmaData=db_asocquery("SELECT valueFloat,valueInt,displayName
                        FROM $LM_EVEDB.`dgmTypeAttributes` AS dta
                        JOIN $LM_EVEDB.`dgmAttributeTypes` AS da
                        ON dta.attributeID=da.attributeID
                        WHERE dta.typeID=$typeID
                        AND displayName != '';");
                    if (count($traitData) > 0) $retdata[$typeID]['traits']=$traitData;
                    if (count($bonusData) > 0) $retdata[$typeID]['bonuses']=$bonusData;
                    if (count($dogmaData) > 0) $retdata[$typeID]['attributes']=$dogmaData;
                }
                $tmpContent=encode($retdata);
                db_uquery("INSERT INTO `lmpagecache` VALUES ('JEREMYBULK','".addslashes($tmpContent)."', NOW()) ON DUPLICATE KEY UPDATE `pageContents`='".addslashes($tmpContent)."', timestamp=NOW();");
                echo($tmpContent);
            }
            break;
        case "MAPREGIONS":
            //regionID - optional
            $regionID=secureGETnum('regionID');
            if (isset($regionID)) $regionWhere="`regionID`=$regionID"; else $regionWhere="TRUE";
            $items=db_asocquery("SELECT * FROM `$LM_EVEDB`.`mapRegions` reg "
                    . "LEFT JOIN `$LM_EVEDB`.`mapLocationScenes` loc ON reg.`regionID` = loc.`locationID` "
                    . "LEFT JOIN `$LM_EVEDB`.`eveGraphics` gra ON loc.`graphicID` = gra.`graphicID` "
                    . "WHERE $regionWhere;");
            if (count($items)==0) RESTfulError('region not found.',404);
            echo(encode($items, 'regions', 'region'));
            break;
        case "MAPCONSTELLATIONS":
            //either regionID or contellationID is mandatory
            $either=FALSE;
            $regionID=secureGETnum('regionID');
            if (isset($regionID)) {
                $regionWhere="`regionID`=$regionID";
                $either=TRUE;
            } else $regionWhere="TRUE";
            $constellationID=secureGETnum('constellationID');
            if (isset($constellationID)) {
                $constWhere="`constellationID`=$constellationID";
                $either=TRUE;
            } else $constWhere="TRUE";
            if (!$either) RESTfulError('Missing either regionID or constellationID parameter.',400);
            $items=db_asocquery("SELECT * FROM `$LM_EVEDB`.`mapConstellations` WHERE $regionWhere AND $constWhere;");
            if (count($items)==0) RESTfulError('constellation not found.',404);
            echo(encode($items, 'constellations', 'constellation'));
            break;
        case "MAPSOLARSYSTEMS":
            //either regionID or contellationID is mandatory
            $either=FALSE;
            $regionID=secureGETnum('regionID');
            if (isset($regionID)) {
                $regionWhere="`regionID`=$regionID";
                $either=TRUE;
            } else $regionWhere="TRUE";
            $constellationID=secureGETnum('constellationID');
            if (isset($constellationID)) {
                $constWhere="`constellationID`=$constellationID";
                $either=TRUE;
            } else $constWhere="TRUE";
            if (!$either) RESTfulError('Missing either regionID or constellationID parameter.',400);
            $items=db_asocquery("SELECT * FROM `$LM_EVEDB`.`mapSolarSystems` WHERE $regionWhere AND $constWhere;");
            if (count($items)==0) RESTfulError('solar systems not found.',404);
            echo(encode($items, 'solarsystems', 'solarsystem'));
            break;
        case "MAPSOLARSYSTEM":
            //solarSystemID - mandatory
            $solarSystemID=secureGETnum('solarSystemID');
            if (empty($solarSystemID)) RESTfulError('Missing solarSystemID parameter.',400);
            $items=db_asocquery("SELECT * FROM `$LM_EVEDB`.`mapDenormalize` WHERE `solarSystemID`=$solarSystemID;");
            if (count($items)==0) RESTfulError('solarSystemID not found.',404);
            echo(encode($items));
            break;
        case "MAPLOCATION":
            //solarSystemID - mandatory
            $itemID=secureGETnum('itemID');
            if (empty($itemID)) RESTfulError('Missing itemID parameter.',400);
            $items=db_asocquery("SELECT * FROM `$LM_EVEDB`.`mapDenormalize` WHERE `itemID`=$itemID;");
            if (count($items)==0) RESTfulError('itemID not found.',404);
            echo(encode($items, 'locations', 'location'));
            break;
	default:	
            RESTfulError('Invalid endpoint.',404);
    }
?>