<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['shoutcast_favorites_qry'];
  } else {
   $session->data['shoutcast_favorites_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_shoutcast_favorites="ID DESC";
  $out['SORTBY']=$sortby_shoutcast_favorites;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM shoutcast_favorites WHERE $qry ORDER BY ".$sortby_shoutcast_favorites);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
