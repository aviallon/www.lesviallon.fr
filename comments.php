<?php
include_once("utility.inc");
include_once("db.inc");

$modid = -1;
if(isset($_POST['mod_id'])){
    $modid = (int)$_POST['mod_id'];
    var_dump($_POST);
}

if(isset($_POST['delete_id']) or isset($_POST['content']) or isset($_POST['mod_content'])){
    //var_dump($_POST);
    
    if(check_session_authenticity($_SESSION) == 0){
        echo "Session corrupted ! Please try again after signing out.";
        redirect('/p/'.$_SESSION['page']."#comments", true);
        die();
    }
    
    $pdo = connect();
    
    if(isset($_POST['delete_id'])){
        
        $request = "SELECT * FROM comments WHERE id = :cid";
        
        $stmt = $pdo->prepare($request);
        
        $stmt->execute(array(
            ":cid"=>(int)$_POST['delete_id']
        ));
        
        $bdd_comment = $stmt->fetch();
        
        var_dump($bdd_comment);
        
        $com_author = $bdd_comment['author'];
        
        if($com_author === (int)$_SESSION['id'] or $_SESSION['edit_level'] >= $__MOD_EDIT_LEVEL){ // Moderators and above have edit level >= 200
            
            $request = "DELETE FROM comments WHERE id = :cid";
            
            $stmt = $pdo->prepare($request);
        
            $stmt->execute(array(
                ":cid"=>(int)$_POST['delete_id']
            ));
        } else {
            var_dump($_POST);
            echo "<br />" . $com_author . "<br />";
            var_dump($_SESSION);
            die("Vous n'avez pas le droit de supprimer ce message");
        }
        
    } elseif(isset($_POST['content'])){
        
        $request = "INSERT INTO comments (author, page_id, content) VALUES (:author, :pid, :content)";
        
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
            ":author"=>$_SESSION['id'],
            ":pid"=>$_POST['pageid'],
            ":content"=>$_POST['content']
        ));
    
    } elseif(isset($_POST['mod_content'])){
        $request = "UPDATE comments SET content = :content WHERE id = :cid";
        
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
            ":content"=>$_POST['mod_content'],
            ":cid"=>(int)$_POST['mod_id']
        ));
    }
    
    redirect('/p/'.$_SESSION['page']."#comments", false);
    die();
}

$pdo = connect();

$request = "SELECT C.*, to_char(C.date, 'DD/MM/YY à HH24:MI:SS') AS date_parsed, U.pseudo FROM comments AS C JOIN users AS U ON U.id = C.author WHERE page_id = :pid ORDER BY date DESC";

$stmt = $pdo->prepare($request);
$stmt->execute(array(
    ":pid"=>$page_id
));
$comments = $stmt->fetchAll();
?>

<script defer>
    function modificationTrick(){
        $("#hidden_mod_content").val($("#modzone").val());
        
        //window.alert($("#hidden_mod_content").val());
        return true;
    }
</script>
<div class="jumbotron mt-5" id="comments">
<h4 class="mb-4">Commentaires</h4>
<?php if(empty($comments)){ ?>
Aucun commentaire !
<?php } else {
    foreach($comments as $comment){ ?>
        <div class="card border border-dark p-2 my-2">
        
            <div class="row card-body p-1">
                <div class="col-2 col-sm-1">
                    <div class="card-img" style="width:100%;">
                        <?php echo createUserLink($comment['pseudo'], false, true); ?>
                    </div>
                </div>
                
                <div class="col-10 card-text col-sm-11 pl-4" id="comment<?php echo $comment['id']; ?>">
                    <?php if($modid === $comment['id']) { ?>
<textarea form="mod_form" name="mod_content" id="modzone">
<?php echo htmlspecialchars($comment['content']); ?>
</textarea>
                    <?php
                    } else { ?>
                        <?php echo bbcode($comment['content']); ?>
                    <?php } ?>
                </div>
            </div>
            
            <div class="row p-1">
                <div class="col-2 col-sm-1">
                </div>
                <div class="col-10 col-sm-11 pl-4 text-right">
                    <em>&Eacute;crit le <?php echo $comment['date_parsed']; ?></em>
                    <?php if($comment['author'] === (int)$_SESSION['id'] or $_SESSION['edit_level'] >= $__MOD_EDIT_LEVEL) { ?>
                        <?php if($modid !== $comment['id']) { ?>
                        
                            <form method="post" class="card-text form-inline" style="display:inline" action="/comments.php">
                                <input type="hidden" name="delete_id" value="<?php echo $comment['id']; ?>" />
                                <button type="submit" class='btn btn-outline-danger' title="Supprimer"><span class="oi oi-trash"></span></button>
                            </form>
                            <form id="mod_form" method="post" class="card-text form-inline" style="display:inline" action="#modzone">
                                <input type="hidden" name="mod_id" value="<?php echo $comment['id']; ?>" />
                                <button type="submit" class='btn btn-outline-success' title="Modifier"><span class="oi oi-pencil"></span></button>
                            </form>
                    
                        <?php } else { ?>
                    
                            <form id="mod_form" method="post" class="card-text form-inline" style="display:inline" action="/comments.php">
                                <input type="hidden" name="mod_id" value="<?php echo $comment['id']; ?>" />
                                <input id="hidden_mod_content" type="hidden" name="mod_content" value="<?php echo htmlspecialchars($comment['content']); ?>" />
                                <button type="submit" class='btn btn-outline-success' title="Modifier" onmousedown="modificationTrick()">OK</button>
                            </form>
                            
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php }
    }
    ?>
    
    <?php if($_SESSION['signed_in']){ ?>
    <form method="post" class="mt-4" action="/comments.php">
        <div class="row">
            <label for="contenu" class="col-form-label"><h6 class="">Nouveau commentaire :</h6></label>
        </div>
        <div class="row">
            <div class="col-8">
                <input id="contenu" class="form-control mr-2" type="text" name="content" />
                <input type="hidden" name="pageid" value="<?php echo $page_id; ?>" style="display:none"/>
            </div>
            <div class="col-4">
                <input type="submit" class='btn btn-outline-secondary form-control' value="Envoyer"/>
            </div>
        </div>
    </form>
    <?php } ?>
</div>
