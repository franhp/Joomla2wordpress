<?php

//Database
$dbuser="root";
$dbpasswd="";
$dbhost="localhost";
$dbname="joomla15";

//Joomla db
$dbprefix="jos_";

//Wordpress db
$destdbprefix="wp_";

$siteurl='localhost/test.org/';

//It will allow you the administrator to log as any user.
$admin_passwd='admin';


/* DO NOT TOUCH */

//Connecting
$link = mysql_connect($dbhost, $dbuser, $dbpasswd);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}


$db = mysql_select_db($dbname, $link);
if (!$db) {
    die ('Wrong table name : ' . mysql_error());
}


/**************************
 
 
 Users
 
 
***************************/


echo "<font color=\"red\">Creating users ...</font>";

$query = "SELECT * FROM ".$dbprefix."users";
$result = mysql_query($query);

while ($register = mysql_fetch_assoc($result)){
    //Simple users
    $pending[]="INSERT INTO ".$destdbprefix."users set id='$register[id]', user_login='$register[username]', user_pass=MD5('$admin_passwd'),
    user_nicename='$register[username]', user_email='$register[email]', user_registered='$register[registerDate]', display_name='$register[username]'";
    $usernames[]=$register[username];
    $ids[]=$register[id];
    //Stuff For comments
    $username=$register[username];
    $user[$username]=$register[id];    
    
    //Roles
    if($register[usertype] == "Super Administrator"){
        $metas[]="INSERT INTO ".$destdbprefix."usermeta set user_id='$register[id]', meta_key='wp_capabilities', meta_value='a:1:{s:13:\"administrator\";b:1;}'";
        $metas[]="INSERT INTO ".$destdbprefix."usermeta set user_id='$register[id]', meta_key='wp_user_level', meta_value='10'";
    }
    else if($register[usertype] == "Author"){
        $metas[]="INSERT INTO ".$destdbprefix."usermeta set user_id='$register[id]', meta_key='wp_capabilities', meta_value='a:1:{s:6:\"author\";b:1;}'";
        $metas[]="INSERT INTO ".$destdbprefix."usermeta set user_id='$register[id]', meta_key='wp_user_level', meta_value='2'";
    }
    else {
        $metas[]="INSERT INTO ".$destdbprefix."usermeta set user_id='$register[id]', meta_key='wp_capabilities', meta_value='a:1:{s:10:\"subscriber\";b:1;}'";    
    }
    $metas[]="INSERT INTO ".$destdbprefix."usermeta set user_id='$register[id]', meta_key='last_name', meta_value='$register[name]'";
}


//Writing the users table

for($i=0;$i<count($pending);$i++){
    $query="SELECT * FROM ".$destdbprefix."users where user_login='$usernames[$i]'";
    $result=mysql_query($query);
    if(mysql_num_rows($result)==0){
        mysql_query($pending[$i]);    
    }
    else{
        
        echo "<br>Duplicate username at ".$usernames[$i].'<br>';
        die('Please clean the following tables: <ul>
            <li>wp_comments
            <li>wp_posts
            <li>wp_terms
            <li>wp_term_taxonomy
            <li>wp_term_relationships
            <li>wp_users
            <li>wp_usermeta
            <li>wp_links
            </ul>
            ');
    }
}


//Writing the users meta information

for($i=0;$i<count($metas);$i++){
    $query2 = "SELECT * FROM ".$destdbprefix."usermeta where user_id='$ids[$i]'";
    $result2 = mysql_query($query2);
    if(mysql_num_rows($result2)==0){
        mysql_query($metas[$i]);    
    }
}

echo "<font color=\"red\">done!.</font><br>";


/**************************
 
 
 Categories
 
 
***************************/

echo "<font color=\"red\">Creating categories ...</font>";

//Frist sections
$query = "SELECT * FROM ".$dbprefix."sections";
$result = mysql_query($query);

while ($register = mysql_fetch_assoc($result)){
    $sections[]="INSERT INTO ".$destdbprefix."terms set term_id='$register[id]', name='$register[name]', slug='$register[alias]'";
    $relations[]="INSERT INTO ".$destdbprefix."term_taxonomy set  term_taxonomy_id='$register[id]' , term_id='$register[id]', taxonomy='category', parent='0', description='$register[description]'";
}

//Then categories
$query = "SELECT * FROM ".$dbprefix."categories";
$result = mysql_query($query);

while($register = mysql_fetch_assoc($result)){
    $newid=$register[id]+10;
    if($register[section]=='com_weblinks') {
        $sections[]="INSERT INTO ".$destdbprefix."terms set term_id='$newid', name='$register[name]', slug='$register[alias]'";
        $relations[]="INSERT INTO ".$destdbprefix."term_taxonomy set  term_taxonomy_id='$newid' , term_id='$newid', taxonomy='link_category', parent='$register[section]', description='$register[description]'";
    }
    if(is_numeric($register[section])){
        $sections[]="INSERT INTO ".$destdbprefix."terms set term_id='$newid', name='$register[name]', slug='$register[alias]'";
        $relations[]="INSERT INTO ".$destdbprefix."term_taxonomy set  term_taxonomy_id='$newid' , term_id='$newid', taxonomy='category', parent='$register[section]', description='$register[description]'";
    }
}

//And write

for($i=0;$i<count($sections);$i++){
    mysql_query($sections[$i]);
}
for($i=0;$i<count($relations);$i++){
    mysql_query($relations[$i]);
}
echo "<font color=\"red\">done!.</font><br>";


/**************************
 
 
 Posts
 
 
***************************/

echo "<font color=\"red\">Creating articles ...</font>";


$query = "SELECT * FROM ".$dbprefix."content";
$result = mysql_query($query);

while ($register = mysql_fetch_assoc($result)){
    $newid=$register[catid]+10;
    $posts[]="INSERT INTO ".$destdbprefix."posts set ID='$register[id]', post_author='$register[created_by]' ,
    post_date='$register[created]', post_content='".addslashes($register[introtext]).addslashes($register[fulltext])."',
    post_title='$register[title]',  guid='http://$siteurl?p=$register[id]', post_status='publish', comment_status='open',
    ping_status='open', post_name='$register[alias]'";
    
    $taxonomy[]="INSERT INTO ".$destdbprefix."term_relationships set object_id='$register[id]', term_taxonomy_id='$register[sectionid]', term_order='0'";
    $taxonomy[]="INSERT INTO ".$destdbprefix."term_relationships set object_id='$register[id]', term_taxonomy_id='$newid', term_order='0'";
    
    $count_posts[$register[sectionid]]++;
    $count_posts2[$newid]++;
    
}

for($i=0;$i<count($posts);$i++){
    mysql_query($posts[$i]);
}
for($i=0;$i<count($taxonomy);$i++){
    mysql_query($taxonomy[$i]);
}


//Setting taxonomy counts
for($i=0;$i<100;$i++){
    if(isset($count_posts[$i])){
        $query="UPDATE ".$destdbprefix."term_taxonomy set count='$count_posts[$i]' where term_taxonomy_id='$i'";
        mysql_query($query);
    }
    if(isset($count_posts2[$i])){
        $newid=$count_posts[$i]+10;
        $query="UPDATE ".$destdbprefix."term_taxonomy set count='$newid' where term_taxonomy_id='$i'";
        mysql_query($query);
    }
}

echo "<font color=\"red\">done!.</font><br>";


/**************************
 
 
 Comments
 
 
***************************/

echo "<font color=\"red\">Creating comments ...</font>";


$query = "SELECT * FROM ".$dbprefix."mxc_comments";
$result = mysql_query($query);

while ($register = mysql_fetch_assoc($result)){
    $id=$register[name];
    $comments[]="INSERT INTO ".$destdbprefix."comments set comment_post_ID='$register[contentid]', comment_author='$register[name]', comment_author_IP='',
    comment_date='$register[date]', comment_content='".addslashes($register[title]).' '.addslashes($register[comment])."', comment_karma='0', comment_approved='1', user_id='$user[$id]'";
    
    
    $count_comments[$register[contentid]]++;
    
}


for($i=0;$i<count($comments);$i++){
    mysql_query($comments[$i]);
}

for($i=0;$i<1000;$i++){
    if(isset($count_comments[$i])){
        $query="UPDATE ".$destdbprefix."posts set comment_count='$count_comments[$i]' where ID='$i'";
        mysql_query($query);
    }

}
echo "<font color=\"red\">done!.</font><br>";


/**************************
 
 
 Weblinks
 
 
***************************/

echo "<font color=\"red\">Creating weblinks ...</font>";

$query = "SELECT * FROM ".$dbprefix."weblinks";
$result = mysql_query($query);

while ($register = mysql_fetch_assoc($result)){
    $links[]="INSERT INTO ".$destdbprefix."links set
    link_id='1000$register[id]',
    link_url='$register[url]',
    link_name='$register[title]',
    link_target='_blank',
    link_description='$register[description]',
    link_visible='Y',
    link_owner='62',
    link_rating='0'";
    
    $newid=$register[catid]+10;
    
    $relate[]="INSERT INTO ".$destdbprefix."term_relationships set
    object_id='1000$register[id]',
    term_taxonomy_id='$newid',
    term_order='0'";
}


for($i=0;$i<count($links);$i++){
    mysql_query($links[$i]);
}
for($i=0;$i<count($relate);$i++){
    mysql_query($relate[$i]);
}

echo "<font color=\"red\">done!.</font><br>";


//DONE
mysql_close($link);
echo "<br>All Done!";

?>