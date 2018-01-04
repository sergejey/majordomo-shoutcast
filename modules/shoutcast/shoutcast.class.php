<?php
/**
* SHOUTCast 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 13:11:29 [Nov 02, 2016])
*/
//
//
class shoutcast extends module {
/**
* shoutcast
*
* Module class constructor
*
* @access private
*/
function shoutcast() {
  $this->name="shoutcast";
  $this->title="SHOUTCast";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();
 $out['API_URL']=$this->config['API_URL'];
 if (!$out['API_URL']) {
  $out['API_URL']='http://';
 }
 $out['API_KEY']=$this->config['API_KEY'];
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];

 $out['API_TERMINAL']=$this->config['API_TERMINAL'];

 if ($this->view_mode=='update_settings') {
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_key;
   $this->config['API_KEY']=$api_key;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;

   global $api_terminal;
   $this->config['API_TERMINAL']=$api_terminal;


   $this->saveConfig();
   $this->redirect("?");
 }

 $out['TERMINALS']=SQLSelect("SELECT NAME, TITLE FROM terminals ORDER BY TITLE");

 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='shoutcast_favorites' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_shoutcast_favorites') {
   $this->search_shoutcast_favorites($out);
  }
  if ($this->view_mode=='edit_shoutcast_favorites') {
   $this->edit_shoutcast_favorites($out, $this->id);
  }
  if ($this->view_mode=='delete_shoutcast_favorites') {
   $this->delete_shoutcast_favorites($this->id);
   $this->redirect("?");
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {

    global $mode;
    if ($mode) {
        $this->mode=$mode;
    }

 $this->getConfig();
    if (!$this->config['API_KEY']) {
        $this->api_key='sh1t7hyn3Kh0jhlV';
    } else {
        $this->api_key=$this->config['API_KEY'];
    }

    $this->limit=100;

    if ($this->mode=='top') {
        $this->topStations($out);
    }
    if ($this->mode=='genres') {
        $this->genres($out);
    }
    if ($this->mode=='search') {
        $this->search_stations($out);
    }

    if ($this->mode=='') {
        $stations=SQLSelect("SELECT STATION_ID as ID, TITLE, '1' as FAVORITE FROM shoutcast_favorites ORDER BY shoutcast_favorites.ID DESC");
        if ($stations[0]['ID']) {
            $total = count($stations);
            for ($i = 0; $i < $total; $i++) {
                $stations[$i]['TITLE_URL']=urlencode($stations[$i]['TITLE']);
                $stations[$i]['TITLE_JS']=addcslashes($stations[$i]['TITLE'],'\'');
            }
            $out['STATIONS']=$stations;
        }
    }

    if ($this->mode=='play') {
        global $station_id;
        global $station_title;

        if ($station_id) {
         $stream_url=$this->getStreamURL($station_id);
        }
        $out['STREAM_URL']=$stream_url;
        $out['STATION_ID']=$station_id;
        $out['TITLE']=$station_title;

    }

    if ($this->mode=='favorites') {
        global $id;
        global $station_id;
        global $station_title;
        global $remove;

        if ($remove && $id) {
            SQLExec("DELETE FROM shoutcast_favorites WHERE STATION_ID=".(int)$id);
            $this->redirect("?");
        }
        if ($station_id) {
            $rec=array();
            $rec['STATION_ID']=$station_id;
            $rec['TITLE']=$station_title;
            SQLExec("DELETE FROM shoutcast_favorites WHERE STATION_ID=".(int)$station_id);
            SQLInsert('shoutcast_favorites',$rec);
        }
        echo 'OK';exit;

    }

    if ($this->mode=='playnow') {
        $this->play();
    }

}

function play() {
    global $station_id;
    global $terminal;

    $stream_url=$this->getStreamURL($station_id);

    if ($stream_url!='') {

        if (!$terminal) {
             $terminal=$this->config['API_TERMINAL'];         
        }
        if (!$terminal) {
            $terminal='HOME';
        }

            $url=BASE_URL.ROOTHTML.'popup/app_player.html?ajax=1';
            $url.="&command=refresh&play_terminal=".$terminal."&play=".urlencode($stream_url);
            $result=getURL($url, 0);
            echo $result;
    }

    exit;

}


function getStreamURL($station_id) {
    $tmp_url='http://yp.shoutcast.com/sbin/tunein-station.m3u?id='.$station_id;
    $data=getURL($tmp_url,0);

    $lines=explode("\n",$data);
    $total = count($lines);
    $streams=array();
    for ($i = 0; $i < $total; $i++) {
        if (trim($lines[$i])!='' && substr($lines[$i],0,1)!='#') {
            $streams[]=$lines[$i];
        }
    }

    $stream_url=$streams[array_rand($streams,1)];
    return $stream_url;
}

function topStations(&$out) {
 $data=$this->api_call('legacy/Top500');

    $stations=$this->parseStations($data);
    $out['STATIONS']=$stations;
    $out['FILTERS']=$this->makeFilters($out['STATIONS']);

}

function genres(&$out) {
    global $parent_id;
    global $genre;
    global $page;

    if (!$genre) {
        if ($parent_id) {
            $out['PARENT_ID']=$parent_id;
            $data=$this->api_call('genre/secondary',array('f'=>'json','parentid'=>(int)$parent_id));
        } else {
            $data=$this->api_call('genre/primary',array('f'=>'json'));
        }
        $list=json_decode($data,true);
        $list_genres=$list['response']['data']['genrelist']['genre'];
        $total = count($list_genres);
        $genres=array();
        for ($i = 0; $i < $total; $i++) {
            $rec=array();
            $rec['ID']=$list_genres[$i]['id'];
            $rec['TITLE']=$list_genres[$i]['name'];
            $rec['TITLE_URL']=urlencode($rec['TITLE']);
            $rec['HAS_CHILDREN']=$list_genres[$i]['haschildren'];
            $rec['COUNT']=$list_genres[$i]['count'];
            $genres[]=$rec;
        }
        $out['GENRES']=$genres;
    } else {
     $out['PARENT_ID']=$genre;
     $out['GENRE']=$genre;

        $page=(int)$page;
        $out['NEXT_PAGE_URL']=$page+1;
        if ($page>0) {
         $out['PREV_PAGE_URL']=$page+1;
        }

        $params=array('f'=>'xml','genre_id'=>$genre,'limit'=> ($page*$this->limit).','.$this->limit);
        $data=$this->api_call('station/advancedsearch',$params);
        $out['STATIONS']=$this->parseStations($data);
        $out['FILTERS']=$this->makeFilters($out['STATIONS']);


    }

    global $title;
    if ($title!='') {
        $out['TITLE']=$title;
    }


}

function parseStations($data) {
    $stations=array();
    if (preg_match_all('/station name="(.+?)".+?id="(\d+?)".+?genre="(.+?)"/uis',$data,$m)) {
        $total = count($m[1]);
        for ($i = 0; $i < $total; $i++) {
            $rec=array();
            $rec['TITLE']=$m[1][$i];
            $rec['TITLE_URL']=urlencode($rec['TITLE']);
            $rec['TITLE_JS']=addcslashes($rec['TITLE'],'\'');
            $rec['ID']=$m[2][$i];
            $rec['GENRE']=$m[3][$i];
            $rec['CLASS']=preg_replace('/\W/','_',$rec['GENRE']);
            $stations[]=$rec;
        }
    }
    return $stations;
}
    
    function makeFilters($stations) {
        $filters=array();
        $seen=array();
        $total = count($stations);
        for ($i = 0; $i < $total; $i++) {
            if (!$seen[$stations[$i]['CLASS']]) {
                $seen[$stations[$i]['CLASS']]=1;
                $filters[]=array('CLASS'=>$stations[$i]['CLASS'],'TITLE'=>$stations[$i]['GENRE']);
            }
        }
        return $filters;
    }

function search_stations(&$out) {
 global $search;
    if ($search!='') {
        global $page;
        $page=(int)$page;
        $out['SEARCH']=htmlspecialchars($search);
        $params=array('f'=>'xml','search'=>$search,'limit'=> ($page*$this->limit).','.$this->limit);
        $data=$this->api_call('legacy/stationsearch',$params);
        $out['STATIONS']=$this->parseStations($data);
        $out['FILTERS']=$this->makeFilters($out['STATIONS']);

    }
}

function api_call($path, $params=0) {
    $url='http://api.shoutcast.com/'.$path.'?';
    if (!is_array($params)) {
        $params=array();
    }

    $params['k']=$this->api_key;

        foreach($params as $k=>$v) {
            $url.='&'.$k.'='.urlencode($v);
        }

    $cached_filename=ROOT.'cached/shoutcast_'.md5($url).'.txt';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Chrome/40.0.2214.111 Safari/537.36');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     // bad style, I know...
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $tmpfname = ROOT . 'cached/cookie.txt';
    curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfname);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfname);

    $result = curl_exec($ch);

    if ($result!='') {
      SaveFile($cached_filename,$result);
    } elseif (file_exists($cached_filename)) {
       $result=LoadFile($cached_filename);
    }

    return $result;
}

    /**
* shoutcast_favorites search
*
* @access public
*/
 function search_shoutcast_favorites(&$out) {
  require(DIR_MODULES.$this->name.'/shoutcast_favorites_search.inc.php');
 }
/**
* shoutcast_favorites edit/add
*
* @access public
*/
 function edit_shoutcast_favorites(&$out, $id) {
  require(DIR_MODULES.$this->name.'/shoutcast_favorites_edit.inc.php');
 }
/**
* shoutcast_favorites delete record
*
* @access public
*/
 function delete_shoutcast_favorites($id) {
  $rec=SQLSelectOne("SELECT * FROM shoutcast_favorites WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM shoutcast_favorites WHERE ID='".$rec['ID']."'");
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS shoutcast_favorites');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data='') {
/*
shoutcast_favorites - 
*/
  $data = <<<EOD
 shoutcast_favorites: ID int(10) unsigned NOT NULL auto_increment
 shoutcast_favorites: TITLE varchar(100) NOT NULL DEFAULT ''
 shoutcast_favorites: STATION_ID int(10) NOT NULL DEFAULT '0' 
 shoutcast_favorites: URL varchar(255) NOT NULL DEFAULT ''
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTm92IDAyLCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
