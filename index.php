<html> 
<head> 
<title>Welcome!</title> 
</head> 
<script src="./jquery.min.js"></script>

<script type='text/javascript'> 
$(document).ready(function(){ 
$("#search_results").slideUp(); 
    $("#search_button").click(function(e){ 
        e.preventDefault(); 
        ajax_search(); 
    }); 
    $("#search_term").keyup(function(e){ 
        e.preventDefault(); 
        ajax_search(); 
    }); 

}); 

var last_search="";

function ajax_search(){ 
  $("#search_results").show(); 
  var search_val=$("#search_term").val(); 

  if (search_val.length < 4) {
      return;
   }

  if (search_val == last_search) {
      return;
  }
  last_search = search_val;

  if (search_val.search(/^\//) >= 0) {
      /*alert("match");*/
      if (search_val.search(/\/$/) == -1) {
          $("#search_results").html("UNTERNMINATED REGEX: <"+search_val+">");
          return;
      }
  }

  $.post("./find.php", {search_term : search_val}, function(data){
      if (data.length>0){ 
         $("#search_results").html(data); 
         sleep(1);
      }  else {
         $("#search_results").html("NO RESULTS"); 
       }
  }) 
} 
</script> 

<body> 
<h1>Search our Phone Directory</h1> 

    <form action="./find.php" id="searchform" method="post"> 
        <div> 
            <label for="search_term">Search name/phone</label> 
            <input type="text" name="search_term" id="search_term" /> 
            <input type="submit" value="search" id="search_button" /> 
        </div> 
    </form> 

    <div id="search_results">
             NO RESULTS YET
    </div> 

<hr>
<h3> USAGE </h3>

<h4> Example queries </h4>

<pre>
Query strings greater than 4 characters (or precede with '-' pad characters)
    e.g. myad will search for 'myad' in name field
         7777 will search for '7777' in numeric fields (telephone/zip)
         --ad will search for 'ad' in name field

Search on 'tag:value' will search in tag where tag is one of 'class', 'name' etc
e.g. class:danse will find all entries of class 'danse'

'xxx#bbb' will search for matching e-mails

Preceding search term by '/' will prevent search whilst trailing is not present.
E.g.
    '/aaa' will not perform a search
    '/aaa/' will search on 'aaa'

Searching on '*xxx' will search for 'xxx' in all fields.

</pre>
    

<hr>
<h3> TODO </h3>

<nl>
<li> Web Services application -> WSDL and RESTful </li>
<li> Test queries with JMeter and SOAPui </li>
<li> Android application - WebService and "local" storage version </li>
<li> Beautify - using CSS </li>
    See <a href="http://www.css3.me"> This tool </a>
<li> Improve data .... missing fields, more telephone types etc ... </li>
<li> More complete form for query </li>
<li> Document syntax (in index page) </li>
<li> Implement counter limit! </li>
<li> Log ip addresses(sym replace HP/Pau/Home), date, query, rows/chars retrieved </li>
<li> Detect browser/UA -> adapt content with CSS </li>
    See <a href="http://stackoverflow.com/questions/1005153/auto-detect-mobile-browser-via-user-agent"> This article </a> and it's links
<li> Hyperlinks to allow calls, send emails, ... </li>
<li> Fix "+" prefix for all fields search </li>
<li> Allow images of people </li>
<li> Allow birthdays of people </li>
<li> Database partioning => old, mdp, <year from entered/modified/filename> ... </li>
<li> <b>DONE:</b> Implement security controls (.htaccess) </li>
</nl>



</body> 
</html> 

