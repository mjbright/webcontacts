<?php
#print "<hr>\n";
#foreach ( $_POST as $key => $value ) {
    #print localtime()."_POST[$key]=>'$value'\n";
    #print "<br>\n";
#}
#print "<hr>\n";
#$page = $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
#print "PAGE=$page\n";
#print "<br>\n";

define("HOST", "localhost");

$user="mjb";
$pw="";
$host=gethostname();
if (preg_match("/amazonaws.com/i", $host)) {
    $user="root";
    $pw="rootpass";
}
if (preg_match("/domU-12-31-39-14-F8-27/i", $host)) {
    $user="root";
    $pw="rootpass";
}
#print "[host=$host] Using $user/$pw\n";
define("USER", $user);
define("PW", $pw);

$DEBUG=0;

$def_search_term="Southmead";
$def_search_term="bright";
$search_term=$def_search_term;

if ($DEBUG) {
    foreach ( $_POST as $key => $value ) { 
        $time=strftime('%c');
        print $time." _POST[$key]=>'$value'\n";
        print "<br>\n";
    }
}
$db=$_POST['db'];

if ($db == "address") {
    $table="address";
    $default_field="name";
    $fields=array('id', 'class', 'name', 'tel', 'mobile', 'email',
                  'address', 'zip', 'country', 'data');
}

if ($db == "media") {
    $table="media";
    $default_field="path";
    $fields=array('id', 'size', 'cksum', 'filename', 'path');
}

if ($db == "mediadisks") {
    $table="mediadisks";
    $default_field="id";
    $fields=array('id', 'TODO', 'TODO', 'TODO', 'TODO');
}

if ($db == "petrol") { print "db=petrol\n"; }
if ($db == "money") { print "db=money\n"; }

if ($DEBUG) {
    print "DataBase=$db\n";
}

define("DB", $db);

function callsCounter() {
    $cnt_file='find_counter.txt';

    $dat=1;

    if (file_exists($cnt_file)) {
        $fh=fopen($cnt_file, 'r');
            $dat=fread($fh, filesize($cnt_file));
            if ($dat == "") { $dat=0; }
            $dat++;
            print "COUNT=$dat\n";
        fclose($fh);
    }
    $fh=fopen($cnt_file, 'w');
        fwrite($fh,$dat);
    fclose($fh);

    return $dat;
}

function getNonPostSearchTerm() {
    global $def_search_term;
    global $search_term;
    global $DEBUG;
    global $argc, $argv;

    if (!empty($argc)) {
        $search_term=$argv[1];
        print "No POST[search_term] set so using arg1:'$search_term'\n";
    } else {
        print "No POST[search_term] set so using default:'$search_term'\n";
        $search_term=$def_search_term;
    }
    $DEBUG=1;
    print "Setting DEBUG mode\n";
    print "\n";
}

if (isset($_POST)) {
    if (isset($_POST['search_term'])) { 
        $search_term=$_POST['search_term'];
    } else {
        print "_POST is set but no search_term\n";
        getNonPostSearchTerm();
    }
}  else {
    print "_POST is unset\n";
    getNonPostSearchTerm();
}


$title="Search results on $db.$table";
print "<title> $title </title>\n";
print "<h1> $title [$search_term] </h1>\n";

#phpInfo();

if ($DEBUG) { print "Connecting to ".HOST." ".USER."\n"; }

if ($DEBUG) { print "Call to connect ...\n"; }
if (! function_exists( "mysql_connect" ) ) {
    print "<H1> ERROR </H1> <b> MySQL is not available in this PHP package\n";
    print "No such function as 'mysql_connect'";
    print "<br>\n";
    print "<br>\n";
    phpInfo();
    exit();
}

#phpInfo();
$link = mysql_connect(HOST,USER,PW);
if ($DEBUG) {
    print "DONE: :Call to connect ...\n";
    print "Connecting to ".HOST." ".USER."\n";
}

/* check connection */
if (mysql_error()) {
    printf("Connect failed: %s\n", mysql_error());
    exit();
}

if (!$link) {
    print "<pre>\n";
    print "Ooops ... Not connected to MYSQL server : " . mysql_error()."\n";
    print "</pre>\n";
    exit();
    #die('Not connected to MYSQL server : ' . mysql_error());
}
if ($DEBUG) {
    print "Connected to MySQL OK\n";
    print "<h1> Connected to MySQL OK </h1>\n";
}

$db_selected = mysql_select_db(DB, $link);
if (!$db_selected) {
    die ("Can't use DB '" . DB . "' : " . mysql_error());
}
if ($DEBUG) {
    print "Connected to DB '" . DB . "' OK\n";
    print "<h1> Connected to DB '".DB."' OK </h1>\n";
}


if ($DEBUG) {
    $debug_sql = "select * from $table";
    $rows = mysql_num_rows(mysql_query($debug_sql, $link));

    print "Number of rows in DB '".DB."' = $rows rows\n";
}

$term = strip_tags(substr($search_term,0, 100));
$term = mysql_escape_string($term); 

$where = modify_search_term($term);

function modify_search_term($term) {
    global $default_field;

    # If ---- inserted to force past 4 char limit:
    # Continue
    if (preg_match("/^-/", $term)) {
        $term=preg_replace("/^-+/", "", $term);
    }

    # Default: search on name only:
    $where="where $default_field like '%$term%'";

    if (preg_match("/^\/(.+)\//", $term)) {
        $term=preg_replace("/^\/(.+)\/$/", '$1', $term);
    }

    if (preg_match("/^([^:]+):/", $term)) {
        $col=$term;
        #preg_replace("/^([^:]+):(.+)$/", '$1', $col);
        $col=preg_replace("/(.+):(.+)/", '$1', $col);
        $term=preg_replace("/^[^:]+:/", "", $term);
        $where = "where $col like '%$term%'\n";

        return $where;
    }

    if (preg_match("/^\*/", $term)) {
        $term=preg_replace("/^\*/", "", $term);
        $where = "where name like '%$term%'\n";
        $where .= "\nor class like '%$term%'\n";
        $where .= "\nor tel like '%$term%'\n";
        $where .= "\nor email like '%$term%'\n";
        $where .= "\nor zip like '%$term%'\n";
        $where .= "\nor country like '%$term%'\n";
        $where .= "\nor data like '%$term%'\n";

        return $where;
    }

    # If @, match on email:
    if (preg_match("/\@/", $term)) {
        $where="where email like '%$term%'";

        return $where;
    }

    # If numeric, match on tel:
    if (preg_match("/^\s*(\d+)\s*$/", $term)) {
        $term=preg_replace("/^\s*(\d+)\s*$/", '$1', $term);
        $where="where tel like '%$term%'";
        $where .= "\nor zip like '%$term%'\n";

        return $where;
    }

    return $where;
}

#my $query = "select * from $table";
#my $query = "select * from $table where name like '%$term%'";
#my $query = "select name,tel,address,country from $table where name like '%$term%'";

$query="select * from $table $where";

doMySqlQuery($query, $link);

#http://maps.google.com/maps?f=q&source=s_q&hl=en&geocode=&q=3,+St+Gabriels+House,+Windermore+Terrace,+Liverpool,+L8+3SB&sll=37.0625,-95.677068&sspn=38.281301,81.123047&ie=UTF8&hq=&hnear=Apartment+3,+St+Gabriels+House,+Windermere+Terrace,+Liverpool,+Merseyside+L8+3SB,+United+Kingdom&z=16
function getMapInfo($val, $country) {
    if ($country == "") { $country="France"; }

    $val=preg_replace("/\s+/", "+", $val);
    return "http://maps.google.com/maps?f=q&source=s_q&hl=en&geocode=&q=".$val;
}

function getIntlNumber($val, $country) {
    #print "VAL=$val country=$country\n";

    if (preg_match("/^\+/", $val)) {
        #print "VAL=$val\n";
        return $val;
        #return $val . " [OK]";
    }

    if (preg_match("/^0/", $val)) {
        $ccode="";
        if ($country == "") { $country="France"; }
        if (preg_match("/^\s*$/", $country)) { $country="France"; }

        if (preg_match("/USA/i", $country)) { $ccode="1"; }
        if (preg_match("/France/i", $country)) { $ccode="33"; }
        if (preg_match("/Italy/i", $country)) { $ccode="39"; }
        if (preg_match("/Spain/i", $country)) { $ccode="34"; }
        if (preg_match("/Portugal/i", $country)) { $ccode="35"; }
        if (preg_match("/Holland/i", $country)) { $ccode="31"; }
        if (preg_match("/Switzerland/i", $country)) { $ccode="41"; }
        if (preg_match("/UK/i", $country)) { $ccode="44"; }
        if (preg_match("/Germany/i", $country)) { $ccode="49"; }

        if ($ccode != "") {
            $val=preg_replace("/^0/", "", $val);
            $val = "+" . $ccode . $val;
        #print "VAL=$val [CC=$ccode]\n";
            return $val;
            #return $val . "[$country]";
        }
     }

        #print "VAL=$val\n";
    return $val;
    #return $val . " [BOF]";
}

function doMySqlQuery($query, $link) {
    global $fields;
    #print mysql_num_rows(mysql_query($query, $link))."\n";
    $query_result = mysql_query($query, $link)
       or die ("Query failed: " . mysql_error() . " Actual query: " . $query);

    $cnt=callsCounter();
    print "<h2> Query </h2>\n";
    print `date`."SQL Query[$cnt]='$query'\n";
    #print "<h2> Query results </h2>\n";
    if (mysql_num_rows($query_result) <= 0){
        print "No matches!\n";
        return;
    }
    $numrows=mysql_num_rows($query_result);
    print "$numrows matches!\n";

    $string="";

    #if (0) {
        #while ($line = mysql_fetch_array($query_result, MYSQL_ASSOC)) {
            #$i=0;
            #foreach ($line as $col_value) {
                #$field=mysql_field_name($result,$i);
                #$fields[$field] = $col_value;
                #$i++;
            #}
       #}
   #}

    print "<table style='DataTable' border='1'>\n<tr>\n";
    foreach ($fields as &$field) {
        print "<th>$field</th>\n";
    }
    print "</tr>\n";

    while($row = mysql_fetch_array($query_result)) {
        #if (++$rownum == 1) {
        #    $numfields = mysql_num_fields($query_result);
        #    print "<tr>\n";
        #    for($i=0; $i<$numfields; $i++) {
        #        print "<th>$fields[$i]</th>\n";
        #    }
        #    print "</tr>\n";
        #}
        print "<tr>\n";
        foreach ($fields as &$field) {
            #print "FIELD=$field\n";
            $val=$row[$field];
            if ($field == "mobile") {
                $val=getIntlNumber($val, $row["country"]);
                $val="<a href='tel:".$val."'>".$val;
            }
            if ($field == "tel") {
                $val=getIntlNumber($val, $row["country"]);
                $val="<a href='tel:".$val."'>".$val;
            }
            if ($field == "address") {
                $http=getMapInfo($val, $row["country"]);
                $val="<a href='".$http."'>".$val;
http://maps.google.com/maps?f=q&source=s_q&hl=en&geocode=&q=3,+St+Gabriels+House,+Windermore+Terrace,+Liverpool,+L8+3SB&sll=37.0625,-95.677068&sspn=38.281301,81.123047&ie=UTF8&hq=&hnear=Apartment+3,+St+Gabriels+House,+Windermere+Terrace,+Liverpool,+Merseyside+L8+3SB,+United+Kingdom&z=16
            }
            if ($field == "email") { $val="<a href='mailto:'".$val."'>".$val; }

            print "<td>" . $val . "</td>\n";
        }
        print "</tr>\n";
    }
    print "</table>\n";
}


?>

