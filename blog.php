<?php
include_once("utility.inc");
include_once("db.inc");

if(isset($_GET['create'])){ ?>

<form method='post' action="/blog.php">
    <div class="form-group">
    <label for="title">Titre:</label>
    <input type="text" class="form-control" id="title" name="title" required minlength="4" maxlength="512" value="" >
    </div>
    <div class="form-group">
        <label for="tags">Tags:</label>
        <input type="text" class="form-control" id="tags" name="tags" maxlength="2048">
    </div>
    <input type="submit" class="btn btn-outline-primary" value="Ajouter">
</form>

<?php
} else if (isset($_POST['title'])){

    if(!check_session_authenticity($_SESSION)){
        echo "Session corrupted !";
        redirect();
    }

    $pdo = connect();
    
    $request = "SELECT G.author_level FROM users JOIN groups AS G ON G.name = users.groupe WHERE users.id = :id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":id"=>$_SESSION['id']
    ));
    
    $user = $stmt->fetch();
    
    $page_name = make_urlslug($_POST['title']);
    
    var_dump($_POST['tags']);

    $request = "INSERT INTO pages (requirements, name, title, edit_level, content, tags, author_id, hidden) VALUES ('{blog}', :name, :title, :editlvl, :content, :tags, :aid, true) RETURNING id AS last_id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":name"=>$page_name,
        ":title"=>$_POST['title'],
        ":editlvl"=>$user['author_level'],
        ":content"=>"_Under redaction..._",
        ":tags"=>to_pg_array(explode(",",$_POST['tags'])),
        ":aid"=>$_SESSION['id']
    ));
    $insertion = $stmt->fetch();
    
    redirect("/p/edit?page_id=".$insertion['last_id']);

} else if (isset($_from_index)){

    $pdo = connect();

    $request = "SELECT P.*, U.pseudo AS author, G.color, G.name AS group_name, to_char(P.date, 'DD/MM/YY') AS date FROM pages AS P JOIN users AS U ON U.id = P.author_id JOIN groups AS G ON G.name = U.groupe WHERE (P.hidden = false OR P.edit_level <= :userlevel OR P.author_id = :userid) AND (requirements @> ARRAY['blog']::varchar[]) ORDER BY P.date DESC";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":userlevel"=>$_SESSION['edit_level'],
        ":userid"=>$_SESSION['id']
    ));

    $posts = $stmt->fetchAll();

    foreach($posts as $post){
        $post = queryFilter($post);
        $lettrine = $post['title'][0];
    ?>
    <div class="media media-custom my-2 <?php if($post['hidden']) { echo "hidden-post"; }?>"  <?php if($post['hidden']) { echo "title='Niveau requis : ".$post['edit_level']."'"; }?>>
        <div class="mr-3 blog-icon unselectable" style="<?php echo colorFromCar($lettrine); ?>">
            <span><?php echo $lettrine; ?></span>
        </div>
    <!--     <img src="/open-iconic/svg/person.svg" class="mr-3" alt="Person"> -->
        <div class="media-body">
            <h5 class="mt-0"><a class="unstyled-link" href="/p/<?php echo $post['name']; ?>"><?php echo $post['title']; ?></a><?php
            $tags = array_db($post['tags']);
            foreach($tags as $tag){?>
             <span class="badge badge-secondary"><?php echo trim(trim($tag, '"'), "'"); ?></span> 
            <?php } ?></h5>
            <div class="blog-summary text-muted">
            <?php
            $summary = substr(searchBbcodeStrip($post['content']), 0, 100)."&hellip;";
            
            echo $summary;
            ?>
            </div>
            <small>de <?php echo createUserLink($post['author']) ;?>, le <?php echo $post['date']; ?>.</small>
        </div>
    </div>
    <?php
    }
    ?>
    <div class="my-3">
    <?php if($_SESSION['signed_in']){ ?>
    <a class="btn btn-primary" href="/p/blog?create=1" title="Ajouter un nouvel article">Nouveau post</a>
    <?php } else {
        echo "Vous devez vous connecter pour écrire un post !";
    } ?>
    </div>
<?php } else {

die("Don't go in there. It's creepy.");

}?>
