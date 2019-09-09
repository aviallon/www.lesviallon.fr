<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use joshtronic\LoremIpsum;
use Highlight\Highlighter;

require_once 'vendor/autoload.php';

/* BEGIN Globals */

$__MOD_EDIT_LEVEL = 200;

/* END Global */

function create_session(){
    session_start();
    
    if(!isset($_SESSION['id']))
        $_SESSION['id'] = -1;
    
    if(!isset($_SESSION['signed_in']))
        $_SESSION['signed_in'] = false;
    
    if(!isset($_SESSION['pseudo']))
        $_SESSION['pseudo'] = "Anonymous";
        
    if(!isset($_SESSION['groupe']))
        $_SESSION['groupe'] = "visitor";
        
    if(!isset($_SESSION['login_error']))
        $_SESSION['login_error'] = "";
        
    if(!isset($_SESSION['edit_level']))
        $_SESSION['edit_level'] = 0;
}

/* BEGIN Execute on each page */

if (session_status() == PHP_SESSION_NONE) {
    create_session();
} /*else if (time() - s) {
    
}*/

setlocale(LC_TIME, "fr_FR");
date_default_timezone_set('Europe/Paris');

/* END Execute on each page */

function clamp($current, $min, $max) {
    return max($min, min($max, $current));
}

function sha512_salted($passwd,$salt)
{
    return hash('sha512',($passwd.md5($salt.'apples')));
}

function to_get($dict){
    $car = "?";
    $url = "";
    foreach(array_slice($dict,1) as $key=>$val){
        $url = $url.$car.urlencode($key)."=".urlencode($val);
        $car = "&";
    }
    return $url;
}

function make_table($title, $content, $exclude){
?>
    <h3><?php echo $title; ?> :</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th scope="col" id="check">X</th>
                    <?php foreach(array_keys($content[0]) as $key) { 
                        if(gettype($key) == 'integer' or (in_array($key,$exclude))){ continue; }
                        $key = htmlentities($key); 
                        $url = $_GET;
                        $url['sort'] = $key;
                        ?>
                    <th scope="col" id="<?php echo $key; ?>" class="<?php if($_REQUEST['sort'] == $key){echo "bg-primary";} ?>"><a class="unstyled-link" href="<?php echo to_get($url); ?>#admin_nav"><?php echo $key; ?></a></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($content as $el) { ?>
                <tr>
                    <td><div><input type="radio" name="select" value="<?php echo $el['id'];?>" /></div></td>
                    <?php foreach($el as $key => $val) {
                        if(gettype($key) == 'integer' or (in_array($key,$exclude))){ continue; }
                    ?>
                    <td><?php echo $val; ?></td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php
}

function redirect($loc='', $slow=false){
    if($loc == ''){
        $loc = '/p/'.$_SESSION['prev_page'];
    }
    if(!$slow){
        try {
            header('Location: '.$loc);
        } catch (Exception $e){
            // Do nothing to handle it.
        }
    }
    die("<br />\nRedirecting...\n".'<script>window.setTimeout(function(){ window.location = "'.$loc.'"; },2000);</script>'); // Fallback with a redirect script (if headers already sent)
}

// Inspired by https://www.softbizscripts.com/free-scripts/create-url-slug-from-text-string-php-8.htm
function make_urlslug($string){
    // Remove spaces from front and end of the string
    
    $string = preg_replace('/[^\\pL\d_]+/u', '-', $string);
    $string = trim($string, "-");
    $string = iconv('utf-8', "us-ascii//TRANSLIT", $string);
    $string = strtolower($string);
    $string = preg_replace('/[^-a-z0-9_]+/', '-', $string);

    //$string = strtolower(trim($string));
    
//     $string = preg_replace(['/[éèê]/','/[àâ]/', '/ù/', '/ç/'], ['e', 'a', 'u', 'c'], $string);
//     
//     Change all non-alphanumeric character sequences to -
//     $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    
    // Change multiple hyphens to single hyphens
    $urlSlug = preg_replace('/-+/', "-", $string);
    
    // Remove trailing - from the slug
    $urlSlug=trim($urlSlug,"-");
    
    return $urlSlug."-".substr(date("U"),-6);
}


/* I don't know if it really is useful nor if it brings any security, but it's better than nothing (for admin tools) */
function check_session_authenticity($session){
    if(!isset($session['authenticity']))
        return 0;
        
    if($session['authenticity'] == sha512_salted($session['groupe'].$session['pseudo'].$session['edit_level'].$session['id'], $session['connection_date'])){
        return 1;
    }
    return 0;
}

function login($id, $pseudo, $groupe, $edit_level) {
    create_session();
    $_SESSION['signed_in'] = true;
    $_SESSION['pseudo'] = $pseudo;
    $_SESSION['id'] = $id;
    $_SESSION['groupe'] = $groupe;
    $_SESSION['edit_level'] = $edit_level;
    $_SESSION['connection_date'] = date_timestamp_get(date_create());
    $_SESSION['authenticity'] = sha512_salted($groupe.$pseudo.$edit_level.$id, $_SESSION['connection_date']);
    $_SESSION['login_error'] = "";
}

function logout(){
    session_destroy();
}

function reset_password($user_id, $message=''){
    
    if($user_id <= 0){
        die("Can't reset $user_id password !");
    }
    
    $pdo = connect();
    
    $request = "SELECT * FROM users WHERE id=:id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":id"=>$user_id
    ));
    $user = $stmt->fetch();
    
    $reset_token = gen_unique_token();
            
    $request = "UPDATE users SET reset_token = :resettkn WHERE id = :id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":id"=>$user_id,
        ":resettkn"=>$reset_token
    ));
    
    if($message == '')
    $message =  "Bonjour ".$user['pseudo'].",<br />".
                "Tu as demandé a reinitialisé ton mot de passe, ou un administrateur l'a fait pour toi.<br />".
                "Tu dois réinitialiser ton mot de passe au lien suivant : <a href='https://www.lesviallon.fr/p/user/?reset=$reset_token'>Changer mot de passe</a><br/>";
    
    send_gmail($user['email'], "Reinitialisation mot de passe (lesviallon.fr)", $message);
}

function escapeCar($car){
    return "<ord>".ord($car)."</ord>";
}

function htmlfullentities($texte, $ordtag = 1){
    if($ordtag == 0){
        return preg_replace_callback("/[^\w]|'/", function($match){
                return "&#".ord($match[0]).";";
        }, $texte);
    } else {
        return preg_replace_callback("/./", function($match){
                return escapeCar($match[0]);
        }, $texte);
    }
}

function escapeText($texte, $escape = true, $include = ""){
    $escapable = "&@_*#`\\\\[\]'\"\(\)~0-9$include-";
    $regex = "";
    if($escape){
        $regex .= "/\\\([";
    } else {
        $regex .= "/([";
    }
    $regex .= $escapable."]|(&gt;))/";
    
    // Provides character escaping.
    $texte = preg_replace_callback($regex, function($matches){
         //return "<ord>".ord($matches[1])."</ord>";
         return htmlfullentities($matches[1], true);
    }, $texte);
    return $texte;
}

function commentspecialchars($texte){
    return preg_replace_callback("/(-->)|(<--)/", function($match){ return htmlfullentities($match[0]); }, $texte);
};

function searchBbcodeStrip($texte){
    $texte = htmlspecialchars($texte);
    
    $texte = escapeText($texte);
    
    $texte = preg_replace("/```(\w*)\s(.+)```/sU", "`$2`", $texte);
    
    $texte = preg_replace("/\[(.+)\]\(([\S]+)(?: '(.+)')?\)/U", "$1", $texte);
    
    $texte = preg_replace(["/__(.+)__/sU", "/_(.+)_/sU", "/\*(.+)\*/sU", "/\*\*(.+)\*\*/sU", "/~~(.+)~~/sU", "/`(.+)`/sU"], ["$1", "$1", "$1", "$1", "$1", "$1"], $texte);
    
    $texte = preg_replace("/#{1,4} (.+)/", "<em>$1</em> :", $texte);
    
    $texte = preg_replace_callback('/<ord>([0-9]+)<\/ord>/', function($matches){
        return chr($matches[1]);
    }, $texte);
    
    return $texte;
}

function bbcode($texte, $search = null){

    $texte = htmlspecialchars($texte);
    
//     $escapable = "@_*#`\\\\[\]\'\"\(\)~0-9-";
//     
//     Provides character escaping.
//     $texte = preg_replace_callback('/\\\(['.$escapable.']|(&gt;))/', function($matches){
//          return "<ord>".ord($matches[1])."</ord>";
//          return htmlfullentities($matches[1], true);
//     }, $texte);
    $texte = escapeText($texte);

    
    //echo "<!-- ".commentspecialchars($texte)." -->";
    
    //echo "<!-- \$escapable : ".commentspecialchars($escapable)." -->";
    
    
    $texte = preg_replace_callback("/```(\w*)\s(.+)```/sU", function($matches){
        $ret = "";
        if($matches[1] == ''){
            $matches[1] = "plaintext";
        }
        $code = $matches[2];
        $hl = new Highlighter();
        try {
            $highlighted = $hl->highlight($matches[1], htmlspecialchars_decode($code));

            $ret .= "<pre><code class=\"hljs {$highlighted->language}\">";
            $ret .= escapeText($highlighted->value, false);
            $ret .= "</code></pre>";
        } catch (DomainException $e) {
            // This is thrown if the specified language does not exist

            $ret .= "<pre><code>";
            $ret .= escapeText($code, false);
            $ret .= "</code></pre>";
        }
        $ret = preg_replace_callback("/\n/", function($match){ return escapeCar($match[0]);}, $ret);
        return $ret;
    }, $texte);
    
    // Images and links
    $texte = preg_replace_callback("/!\[(.+)\]\((.+)(?: \"(.+)\")?\)/U", function($matches){
        return "<img src='".htmlfullentities($matches[2])."' title='".htmlfullentities($matches[1])."' alt='".htmlfullentities($matches[1])."' />";
    }, $texte);
    $texte = preg_replace_callback("/\[(.+)\]\(([\S]+)(?: '(.+)')?\)/U", function($matches){
        $title = escapeText($matches[1], false);
        if(isset($matches[3]))
            $title = escapeText($matches[3],false);
        return "<a href='".escapeText($matches[2], false)."' title='".$title."' alt='".escapeText($matches[1],false, 0)."'>".escapeText($matches[1], false)."</a>";
    }, $texte);
    
    $texte = preg_replace(["/__(.+)__/sU", "/\*\*(.+)\*\*/sU"], ["<strong>$1</strong>", "<strong>$1</strong>"], $texte);
    $texte = preg_replace(["/_(.+)_/sU", "/\*(.+)\*/sU"], ["<em>$1</em>", "<em>$1</em>"], $texte);
    
    if(null !== $search){
        $search_iterator = 0;
        $texte = preg_replace_callback("/([^<].*?)(".preg_quote($search).")(.*?[^>])/si", function($matches){
            global $search_iterator;
            $found = $matches[1];
            $search_iterator++;
            return  $matches[1]."<span class='search-mark' id='mark".$search_iterator."'>".$matches[2]."</span>".$matches[3];
        }, $texte);
    }
    
    $texte = preg_replace_callback("/`(.+)`/sU", function($matches){
        return "<code style='display:inline-block'>".$matches[1]."</code>";
    }, $texte);
    
    $texte = preg_replace("/~~(.+)~~/sU", "<s>$1</s>", $texte);
    
    $texte = preg_replace("/#### (.+)/", "<h6>$1</h6>", $texte);
    $texte = preg_replace("/### (.+)/", "<h5>$1</h5>", $texte);
    $texte = preg_replace("/## (.+)/", "<h4>$1</h4>", $texte);
    $texte = preg_replace("/# (.+)/", "<h3>$1</h3>", $texte);
    
    $texte = preg_replace("/ @([a-zA-Z0-9]+)/", " <a href='/u/$1' title='Voir utilisateur'>@$1</a>", $texte);
    
    
    // Implicit block styling
    $texte = preg_replace_callback("/\n((?:".htmlspecialchars(">")." (?:.+)\n)+)/", function($matches) {
        return "<<BEGIN_QUOTE>>\n".preg_replace("/".htmlspecialchars(">")."/", "", $matches[1])."<<END_QUOTE>>";
    }, $texte);
    $texte = preg_replace("/<<BEGIN_QUOTE>>(.+)<<END_QUOTE>>/sU", "<blockquote class='blockquote'>$1</blockquote>", $texte);
    
    $texte = preg_replace_callback("/\n((?:".htmlspecialchars("-")." (?:.+)\n)+)/", function($matches) {
        $content = preg_replace("/".htmlspecialchars("-")."(.+)/", "<li>$1</li>", $matches[1]);
        $content = preg_replace_callback("/\n/", function($match){ return escapeCar($match[0]);}, $content);
        return "<<BEGIN_LIST>>\n".$content."<<END_LIST>>";
    }, $texte);
    $texte = preg_replace("/<<BEGIN_LIST>>(.+)<<END_LIST>>/sU", "<ul>$1</ul>", $texte);
    
    $texte = preg_replace_callback("/\n((?:".htmlspecialchars("[0-9]+.")." (?:.+)\n)+)/", function($matches) {
        $content = preg_replace("/".htmlspecialchars("[0-9]+.")."(.+)/", "<li>$1</li>", $matches[1]);
        $content = preg_replace_callback("/\n/", function($match){ return escapeCar($match[0]);}, $content);
        return "<<BEGIN_OLIST>>\n".$content."<<END_OLIST>>";
    }, $texte);
    $texte = preg_replace("/<<BEGIN_OLIST>>(.+)<<END_OLIST>>/sU", "<ol>$1</ol>", $texte);
    
    
    $texte = preg_replace_callback("/\\\lorem{([0-9]+)}/", function($matches){
        $lipsum = new LoremIpsum();
        return "<span title='Auto-generated Lorem Ipsum :)'>".$lipsum->sentences((int)$matches[1])."</span>";
    }, $texte);
    
    $texte = preg_replace("/\.\.\.([^\w]?)/sU", "&hellip;$1", $texte);
    
    $texte = preg_replace("/\n+/sU", "\n", $texte);
    
//     $texte = preg_replace_callback("/((?:(?:.+)\n)+)/", function($matches) {
//         return "<<BEGIN_PARAGRAPH>>".preg_replace("/(\n)*$/", "", $matches[1])."<<END_PARAGRAPH>>";
//     }, $texte);
//     $texte = preg_replace("/<<BEGIN_PARAGRAPH>>(.+)<<END_PARAGRAPH>>/sU", "<p>$1</p>", $texte);
    
    
    
    $texte = preg_replace("/\n/", "<br />", $texte);
    
    $texte = preg_replace_callback('/<ord>([0-9]+)<\/ord>/', function($matches){
        return chr($matches[1]);
    }, $texte);
    
    return $texte;
}

function createUserLink($username, $italic = false, $avatar = false){

    $pdo = connect();
    
    $request = "SELECT U.pseudo, G.name AS group_name, G.color AS group_color FROM users AS U JOIN groups AS G ON U.groupe = G.name WHERE U.pseudo = :user";
    
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":user"=>$username
    ));
    
    $bdd_answer = $stmt->fetch();
    
    $color = "grey";
    $groupname = "Inconnu";
    $link = "Anonymous";
    
    if(!empty($bdd_answer)){
        $color = $bdd_answer['group_color'];
        $groupname = $bdd_answer['group_name'];
        $link = $username;
    }
    
    if($italic){
        $italic = "font-style:italic;";
    }
    
    $texte = "";
    
    if($avatar){
        $color = colorFromCar($username[0]);
        $groupname = ucfirst($groupname);
        $texte = <<<EOT
<a class="unstyled-link" href="/u/$link" title="$username ($groupname)">
    <div class="user-icon" style="$color">
        <span class="unselectable">$username[0]</span>
    </div>
</a>
EOT;
        } else {
    
        $texte = "<a href='/u/".$link."' style='color:".$color.";$italic' title='".ucfirst($groupname)."'>".$username."</a>";
    
    }
    
    return $texte;
}

function colorIsDark($r, $g, $b){

    $luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b; // per ITU-R BT.709

    if ($luma < 40) {
        return true;
    }
    return false;
}

function colorFromCar($car){
    $x = 2*pi()*clamp(ord($car)-32, 0, 127-32)/(127-32);
    $r = 128+127*sin($x);
    $g = 128+127*sin($x + pi()/3);
    $b = 128+127*sin($x + 2*pi()/3);
    $white = "";
    if(colorIsDark($r, $g, $b)){
        $white = "color:white;";
    }
    return "background-color:".sprintf("#%02x%02x%02x",
        $r,
        $g,
        $b
    ).";$white";
}

function randhex($length=32){
    $nbytes = (int)($length/2);
    $token = "";
    try {
        $token = bin2hex(random_bytes($nbytes));
    } catch (Exception $e){
        $token = bin2hex(openssl_random_pseudo_bytes($nbytes), $strong);
        
        if(!$strong){
            error_log("Warning : tokens not cryptographically secure here !");
        }
    }
    return $token;
}

function gen_unique_token($entropy=''){
    return hash('sha512',(microtime(true).random_bytes(16).$entropy));
}

function send_gmail($to, $subject, $content){
    $mail = new PHPMailer(true);
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port     = 587;  
    $mail->Username = "antoine.viallon";
    $mail->Password = "7vfcdxswq";
    $mail->Host     = "smtp.gmail.com";
    $mail->Mailer   = "smtp";
    $mail->SetFrom("antoine.viallon+lesviallon@gmail.com", "Admin Les Viallon");
    $mail->AddReplyTo("antoine.viallon+lesviallon@gmail.com", "Admin Les Viallon");
    $mail->AddAddress($to);
    $mail->Subject = $subject;
    $mail->WordWrap   = 80;
    $mail->MsgHTML($content);
    $mail->IsHTML(true);
    if(!$mail->Send()) {
        echo "Problem sending email.";
        return 0;
    } else {
        echo "Email sent.";
        return 1;
    }
}
?>
