<?php
include_once("utility.inc");
include_once("db.inc");
if(!check_session_authenticity($_SESSION)){
    die('Session corrupted. Please logout and login again.');
}

if(isset($_REQUEST['page_id'])){

    $pdo = connect();
    
    $request = "SELECT G.edit_level FROM users JOIN groups AS G ON G.name = users.groupe WHERE users.id = :id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":id"=>$_SESSION['id']
    ));
    $user = $stmt->fetch();
    
    $request = "SELECT P.*, U.pseudo AS author FROM pages AS P JOIN users AS U ON U.id = P.author_id WHERE P.id = :id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":id"=>$_REQUEST['page_id']
    ));
    $pages = $stmt->fetchAll();
    
    if(count($pages) == 0){
        echo "Page inexistante !";
        die();
    }
    
    $page = $pages[0];
    
    $ok = false;
    if($_SESSION['pseudo'] == $page['author'] and $page['edit_level'] < 1000){ // If a user created an article as admin and then got retrograded, he loses his right to edit his own post !
        $ok = true;
    }
    if($page['edit_level'] <= $user['edit_level']){
        $ok = true;
    }
    
    if(!$ok){
        $_SESSION['error'] = 'insufficient_privileges';
        //redirect('/p/'.$page['name']);
        echo "Insufficient privileges !";
        redirect('/p/'.$page['name']);
        //die("Insufficient privileges ! Redirecting...".'<script>window.setTimeout(function(){ window.location = "/p/home"; },3000);</script>');
    }
    
    $content = $page['content'];
    $title = $page['title'];
    $appendReturn = "";
    if(isset($_REQUEST['content'])) {
        
        if($_REQUEST['content'][strlen($_REQUEST['content'])-1] != "\n"){
            $appendReturn = "\n";
        }
    
        $request = "UPDATE pages SET content = :content, title = :title, modifier_id = :mid WHERE id = :id RETURNING *";
        $stmt = $pdo->prepare($request);
        $success = $stmt->execute(array(
            ":id"=>$_REQUEST['page_id'],
            ":content"=>$_REQUEST['content'].$appendReturn,
            ":title"=>$_REQUEST['title'],
            ":mid"=>$_SESSION['id']
        ));
        //$page = $stmt->fetch();
        if($success){
            $content = $_REQUEST['content'].$appendReturn;
            $title = $_REQUEST['title'];
        }
    } else if (isset($_REQUEST['hide'])){
        $hide = 't';
        if($_REQUEST['hide'] == '0'){
            $hide = 'f';
        }
        
        $request = "UPDATE pages SET hidden = :hide WHERE id = :id RETURNING hidden";
        $stmt = $pdo->prepare($request);
        $success = $stmt->execute(array(
            ":id"=>$_REQUEST['page_id'],
            ":hide"=>$hide
        ));
        
        $res = $stmt->fetch();
        
        $page['hidden'] = $res['hidden'];
        
        //var_dump(queryFilter($res));
        //var_dump($hide);
        redirect("/p/".$page['name']);
    }
    
?>
<h2>Édition de la page "<a href="/p/<?php echo $page['name']; ?>" class="unstyled-link" title="Revenir à la page"><?php echo $page['name']; ?></a>"</h2>
<h6>Auteur : <em><?php echo $page['author']; ?></em></h6>
<?php if(isset($_REQUEST['content'])){
if ($success) { ?>
<div class="alert alert-success" role="alert">Page éditée avec succès !</div>
<?php } else { ?>
<div class="alert alert-danger" role="alert">Erreur lors de l'édition !</div>
<?php }
} ?>
<form method="post" action="/p/edit?page_id=<?php echo $_GET['page_id'];?>">
<input type="text" name="title" value="<?php echo $title;?>" />
<br />
<textarea id="editor" style="width:80%;margin-left:auto;margin-right:auto;min-height:150px;" name="content">
<?php echo $content; ?>
</textarea>
<!--<div id="editor-container">

</div>-->
<br />
<input type="hidden" style="display:none;" name="page_id" value="<?php echo $_GET['page_id'];?>"/>
<button type="submit" class="btn btn-primary">Editer</button>
<?php if($page['hidden']) { ?>
<a class="btn btn-danger" href="/edit.php?hide=0&amp;page_id=<?php echo $_GET['page_id']; ?>">Rendre public</a>
<?php } else { ?>
<a class="btn btn-danger" href="/edit.php?hide=1&amp;page_id=<?php echo $_GET['page_id']; ?>">Cacher</a>
<?php } ?>
</form>
<script>
var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("editor"), {
mode: "markdown"
});
</script>
<?php } else { ?>
<div class="alert alert-danger" role="alert">Veuillez indiquer un id de page à éditer !</div>
<?php }
?>
