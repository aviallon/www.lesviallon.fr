<?php
include_once("utility.inc");
include_once("db.inc");

$_from_index = true; // Say we are coming from the index page. Useful for scripts.

/* BEGIN page history save */
/* This block aims to save previously viewed page in $_SESSION, and save the newly requested one in $_SESSION too. Useful for scripts and all. */
if(isset($_GET['page'])){
    $_page = $_GET['page'];
} else {
    $_page = 'home';
}

if(isset($_SESSION['page'])){
    if($_SESSION['page'] != 'login'){
        $_SESSION['prev_page'] = $_SESSION['page'];
    }
}
$_SESSION['page'] = $_page;
/* END page history save */

// Redirections for Nextcloud... not needed anymore actually (uses its own subdomain)
if($_SESSION['page'] == 'nextcloud' or $_SESSION['page'] == 'lab'){
    header('Location: /'.$_SESSION['page'].'/index.php');
    die;
}

$search_req = "";
if(isset($_GET['q'])){
    $search_req = $_GET['q'];
}


/* BEGIN monster BDD part */
$pdo = connect();
    
$request = "SELECT P.hidden, P.id AS page_id, P.requirements, P.name, P.content, P.title, P.special, P.edit_level, to_char(P.date, 'DD/MM/YY à HH24:MI:SS') AS date, to_char(P.moddate, 'DD/MM/YY à HH24:MI:SS') AS moddate, U.pseudo AS pseudo, mU.pseudo AS mod_pseudo, G.name AS group_name, G.color AS group_color, mG.name AS mgroup_name, mG.color AS mgroup_color FROM pages AS P JOIN users AS U ON U.id = P.author_id JOIN users AS mU ON mU.id = P.modifier_id JOIN groups AS G ON G.name = U.groupe JOIN groups AS mG ON mG.name = mU.groupe WHERE P.name=:n AND (P.hidden = false OR P.edit_level <= :userlevel OR P.author_id = :userid)";
$stmt = $pdo->prepare($request);
$stmt->execute(array(
    ":n"=>$_SESSION['page'],
    ":userlevel"=>$_SESSION['edit_level'],
    ":userid"=>$_SESSION['id']
));
$bdd_answer = $stmt->fetch(); // There can be only one result since 'name' is a primary key

$page_id = $bdd_answer['page_id']; // Used in comments
$content = '';
$modification = '';
$requirements = array_db($bdd_answer['requirements']);

if(isset($bdd_answer['content'])){ // Check that we really did get a result
    $content = $bdd_answer['content'];
    if($bdd_answer['moddate'] != ''){
        $modification = "<em>Modifié le</em> ".$bdd_answer['moddate']." par ".createUserLink($bdd_answer['mod_pseudo'], true);
    }
}


/* END monster BDD part */
?>
<!doctype html>
<html lang="fr">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Site de la famille Viallon">
    <meta name="author" content="Antoine Viallon">
    <meta name="google-site-verification" content="XFLNwY1cnzqzP12Gl-xO0mq-igSN8z2l4tg0uRES-E0" />
    
    <meta name="docsearch:language" content="fr">
    <meta name="theme-color" content="<?php echo sprintf("#%02x%02x%02x", rand(0,255), rand(0,255), rand(0,255)); ?>">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="/open-iconic/font/css/open-iconic-bootstrap.css" rel="stylesheet">
    
    <!-- Syntax highlighting -->
    <link href="/vendor/scrivo/highlight.php/styles/default.css" rel="stylesheet">

    <title>Site des Viallon<?php switch($_SESSION['page']){
        case 'animes':
            echo " - Animés";
            break;
        case 'blog':
            echo " - Blog";
            break;
        case 'search':
            echo " - Recherche";
            break;
        default:
            
    }?></title>
    
    <!-- Global styling -->
    <link href="/style.scss" rel="stylesheet">
    
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-48177924-2"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-48177924-2');
    </script>
    
    <!--    
    $requirements :
    <?php print_r($requirements); ?>
    -->
    
    <?php if(in_array("videojs", $requirements)){ ?>
    <link href="https://vjs.zencdn.net/7.4.1/video-js.min.css" rel="stylesheet">
    <script src="https://vjs.zencdn.net/7.4.1/video.min.js"></script>
    <?php } ?>
    
    <?php if(in_array("codemirror", $requirements)) { ?>
    <link rel="stylesheet" href="/CodeMirror/lib/codemirror.css">
    <script src="/CodeMirror/lib/codemirror.js"></script>
    <script src="/CodeMirror/mode/markdown/markdown.js"></script>
    <?php } ?>

  </head>
  <body>
    <div class="container-fluid">
        <?php
        include "nav.inc";
        ?>
        
        
        <div class="jumbotron page">
            <?php
            switch($_SESSION['page']){
                case 'login':
                    include 'login.php';
                    break;
                case 'animes':
                    include 'animes.inc';
                    break;
                case 'blog':
                    include 'blog.php';
                    break;
                case 'search':
                    include 'search.inc';
                    break;
                case 'user':
                    include 'user.php';
                    break;
                case 'edit':
                    include 'edit.php';
                    break;
                case 'admin':
                    if(check_session_authenticity($_SESSION) == 0){
                        echo "Session corrupted ! Please try again after signing out.";
                        break;
                    } else if($_SESSION['groupe'] == 'admin'){
                        include 'admin.php';
                        break;
                    }
                default:
                    if($content != ''){
                        echo '<h1>'.htmlspecialchars($bdd_answer['title'])."</h1>";
                        if($bdd_answer['hidden']){
                            echo "<h6><em>Hidden</em> (".$bdd_answer['edit_level'].")</h6>";
                        }
                        $search = null;
                        if(isset($_GET['q'])){
                            $search = $_GET['q'];
                        }
                        $content =  bbcode($content, $search);
                        echo $content;
                        $edit_btn = '';
                        if( ($bdd_answer['edit_level'] <= $_SESSION['edit_level'] or $bdd_answer['pseudo'] == $_SESSION['pseudo']) and $_SESSION['signed_in'] ){
                            $edit_btn = "<a href='/p/edit?page_id=".$bdd_answer['page_id']."' class='btn btn-outline-secondary'>&Eacute;diter</a>";
                        }
                        echo "<br /><br />\n<small>Par ".createUserLink($bdd_answer['pseudo'], true).", le ".$bdd_answer['date'].". $modification</small> ".$edit_btn;
                        
                        if(!in_array("nocomments", $requirements)) {
                            include "comments.php";
                        }
                    } else {
            ?>
                <h1>Erreur 404</h1>
                <p>Oups ! La page à laquelle vous essayez d’accéder... n'existe pas !</p>
            <?php
                    }
            }
            ?>
        </div>
        
        
        <footer class="b-flex justify-content-between">
        <?php if($_SESSION['groupe'] == 'admin') { ?>
        <span><em>Page précédente : </em><?php if(isset($_SESSION['prev_page'])){ echo $_SESSION['prev_page']; } ?></span>
        <a class="btn btn-outline-danger" href="/p/admin" title="Console administrateur">Admin</a>
        <?php } ?>
        </footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

  </body>
</html>
