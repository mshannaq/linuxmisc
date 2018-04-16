<?php
/*
 * wpass.php
 *
 * allows changing of wordpress passwords via cmd line
 * 
 * Mohammed AlShannaq, http://ms.per.jo
 *
 * https://github.com/mshannaq/linuxmisc
 */

abstract class BaseCMS {

    protected $host;
    protected $user;
    protected $pass;
    protected $dbname;
    protected $location;
    protected $prefix;
    protected $cms;
    protected $uname;

    protected function randpass(){
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
            'abcdefghijklmnopqrstuvwxyz0123456789';

        $str = '';
        $count = strlen($charset);
        for ($i = 0; $i <= 9; $i++) {
            $str .= $charset[mt_rand(0, $count-1)];
        }

        return $str;
    }

    abstract protected function adminQuery();
    abstract protected function chpassQuery($data, $password);

    public function run() {

        mysql_connect($this->host, $this->user, $this->pass)
            or die(mysql_error());

        mysql_select_db($this->dbname) or die(mysql_error());

        $result = mysql_query($this->adminQuery()) or die (mysql_error());

        print "\n[".$this->cms."] Location: ".$this->location."\n===\n";
        while ($data = mysql_fetch_array($result)) {
            $password = $this->randpass();
            mysql_query($this->chpassQuery($data, $password)) or die (mysql_error());

            print $data[$this->uname]." - ".$password."\n";
        }
        print "\n\e[5mOriginal hashed password are stored in passtmp file for reference , Don't forget to delete it if you wont it\033[0m\n";
        print "===\n";

        mysql_close();
    }
}

class WpCMS extends BaseCMS {

    function __construct($dir) {
        $this->location = dirname(realpath($dir."/wp-config.php"));
        $wpconfig = $this->wphack($dir."/wp-config.php");
        require($wpconfig);

        $this->host = DB_HOST;
        $this->user = DB_USER;
        $this->pass = DB_PASSWORD;
        $this->dbname = DB_NAME;
        $this->prefix = $table_prefix;
        $this->cms = "WordPress";
        $this->uname = "user_login";

        unlink('./wpresstmp.php');
    }

    protected function wphack($confFile) {
        $tmp = './wpresstmp.php';
        copy($confFile, $tmp);
        //$evil = "require_once(ABSPATH . 'wp-settings.php');";
        $evil = "/require_once\(\s*?ABSPATH.*?'wp-settings.php'\s*?\);/";
        $data = file_get_contents($tmp);
        //$data = str_replace($evil,"",$data);
        $data = preg_replace($evil,"",$data);
        file_put_contents($tmp, $data);

        return $tmp;
    }

    protected function adminQuery() {
        return "SELECT um.user_id AS id, u.user_login , u.user_pass FROM ".$this->prefix.
            "users u,".$this->prefix."usermeta um WHERE u.id = um.user_id ".
            "AND um.meta_key = '".$this->prefix."capabilities' AND ".
            "um.meta_value LIKE '%administrator%'";
    }

    protected function chpassQuery($data, $password) {
        $this->passStore($data);
        return "UPDATE ".$this->prefix."users SET user_pass = MD5('".
            $password."') WHERE id=".$data['id'];
    }
    
    protected function passStore($data){
        $strotmp = './passtmp';        
        $stortmp_file = fopen($strotmp, "w");
        $stor_string = $data['user_login']. "|".$data['user_pass'];
        fwrite($stortmp_file, $stor_string);  
        fclose($stortmp_file);

    }
}


class CMSFactory {

    public static function create($dir) {

        print "\n\n\033[0;31mPlease note that this script still in development mode.\n";       
        print "https://wiki.ms.per.jo/index.php/Wpass.php\033[0m \n\n";

        if (file_exists($dir.'/wp-config.php')) {
            return new WpCMS($dir);        
        } else {
            return null;
        }
    }
}


$dir = ".";
if (sizeof($argv) > 1) {
    $dir = $argv[1];
}

$cms = CMSFactory::create($dir);
if ($cms == null) {
    file_put_contents('php://stderr', "Could not find WordPress CMS in the current folder\n");
    exit(1);
}


$cms->run();