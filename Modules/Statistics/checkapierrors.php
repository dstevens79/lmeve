<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.


 
 */

	checksession(); //check if we are called by a valid session
        $sql="SELECT *
            FROM `apistatus`
            WHERE date >= DATE_SUB( NOW( ) , INTERVAL 1 HOUR )
            AND errorCode >0
            ORDER BY date DESC
            LIMIT 0,3;";
	$errors = db_asocquery($sql);
        foreach ($errors as $error) {
            ?>
            <div class="newmsg">
                <img src="<?=getUrl()?>img/exc.gif" alt="Warning" />
                <a href="../Statistics/?id=8&id2=4"><?=$error['fileName']?>: <?=$error['errorMessage']?></a>
            </div>
            <?php
        }
        
        /* ESI */
        $sql="SELECT *
            FROM `esistatus`
            WHERE date >= DATE_SUB( NOW( ) , INTERVAL 1 HOUR )
            AND errorCode >200
            ORDER BY date DESC
            LIMIT 0,3;";
	$errors = db_asocquery($sql);
        foreach ($errors as $error) {
            ?>
            <div class="newmsg">
                <img src="<?=getUrl()?>img/exc.gif" alt="Warning" />
                <a href="../Statistics?id=8&id2=7">ESI error on route <?=$error['route']?>: <?=$error['errorMessage']?></a>
            </div>
            <?php
        }
        
?>
