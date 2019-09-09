<?php
include_once("utility.inc");
include_once("db.inc");

/* Keep away intruders */
if(check_session_authenticity($_SESSION) == 0){
    echo "Session corrupted ! Please try again after signing out.";
    die();
}
if($_SESSION['groupe'] != 'admin'){
    die("What the fuck are you doing here ???? Go away and close that door !");
}


// Start doing anything meaningful from here.

if(isset($_REQUEST['impersonate'])){

    $pdo = connect();
            
    $request = "SELECT users.*, G.edit_level FROM users JOIN groups AS G ON G.name = users.groupe WHERE users.id=:id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":id"=>$_POST['select']
    ));
    $user = $stmt->fetch();
    
    logout();
    login($user['id'], $user['pseudo'], $user['groupe'], $user['edit_level']);
    redirect("/p/home");
    //die("It works !");
}

if(isset($_SESSION['_POST'])){
    $_POST = $_SESSION['_POST'];
    unset($_SESSION['_POST']);
    $_GET = $_SESSION['_GET'];
    unset($_SESSION['_GET']);
    $_REQUEST = $_SESSION['_REQUEST'];
    unset($_SESSION['_REQUEST']);
}



if(isset($_REQUEST['redirect'])){
    unset($_REQUEST['redirect']);
    $_SESSION['_POST'] = $_POST;
    $_SESSION['_GET'] = $_GET;
    $_SESSION['_REQUEST'] = $_REQUEST;
    
    redirect("/p/admin");
}


if(isset($_REQUEST['mod'])){
    
//     $need_password_reset = false;
//     $password = "";
//     if($_POST['password'] != ''){
//         $password = $_POST['password'];
//     } else {
//         $password = gen_unique_token();
//         $need_password_reset = true;
//     }

    $groupe = $_POST['groupe'];
    $pseudo = htmlspecialchars($_POST['pseudo']);
    $password = "";
    $password_hash = "";
    if($_POST['password'] != ''){
        $password = $_POST['password'];
        $password_hash = sha512_salted($password, $pseudo);
    }
    
//     $reset_token = "";
//     if($need_password_reset){
//         $reset_token = gen_unique_token();
//     }

    $pdo = connect();
    
    if($password == "") {
        $request = "UPDATE users SET pseudo = :pseudo, email = :email, groupe = :group WHERE id = :id";
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
            ":pseudo"=>$pseudo,
            ":email"=>$_POST['email'],
            ":group"=>$groupe,
            ":id"=>$_POST['id']
        ));
    } else {
        $request = "UPDATE users SET pseudo = :pseudo, email = :email, groupe = :group, password = :pass WHERE id = :id";
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
            ":pseudo"=>$pseudo,
            ":email"=>$_POST['email'],
            ":group"=>$groupe,
            ":pass"=>$password_hash,
            ":id"=>$_POST['id']
        ));
    }
        

    redirect("/p/admin/?tab=users#admin_nav");
}


/* BEGIN form management here */

if(isset($_POST['pseudo'])){
    
    $need_password_reset = false;
    $password = "";
    if($_POST['password'] != ''){
        $password = $_POST['password'];
    } else {
        $password = gen_unique_token();
        $need_password_reset = true;
    }
    $groupe = "user";
    if($_POST['groupe'] != ''){
        $groupe = $_POST['groupe'];
    }
    $pseudo = htmlspecialchars($_POST['pseudo']);
    $password_hash = sha512_salted($password, $pseudo);
    
    $reset_token = "";
    if($need_password_reset){
        $reset_token = gen_unique_token();
    }
    
    /* BEGIN add user ! */

        $pdo = connect();
            
        $request = "INSERT INTO users (pseudo, password, email, groupe, reset_token) VALUES (:ps, :pass, :email, :group, :resettkn)";
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
            ":ps"=>$pseudo,
            ":pass"=>$password_hash,
            ":email"=>$_POST['email'],
            ":group"=>$groupe,
            ":resettkn"=>$reset_token
        ));
        
        $message =  "Bienvenue sur <a href='https://www.lesviallon.fr/'>lesviallon.fr</a>, $pseudo.<br />".
                    "Tu peux maintenant poster des commentaires, accèder à des sections réservées aux membres, etc.<br />";
                    
        if($need_password_reset) {
            $message .= "Tu dois réinitialiser ton mot de passe au lien suivant : <a href='https://www.lesviallon.fr/p/user/?reset=$reset_token'>Changer mot de passe</a><br/>";
        } else {
            $message .= "Ton mot de passe a été prédéfini par l'administrateur. Si tu ne le connais pas, demande à <a href='https://www.lesviallon.fr/u/".$_SESSION['pseudo']."'>".$_SESSION['pseudo']."</a>, c'est lui qui a créé ton compte<br />";
        }
        
        $message .= "<em>PS : Comme ton compte a été manuellement créé par un admin, tu n'as pas besoin de le 'vérifier'. Pratique, non ?</em>";
        
        send_gmail($_POST['email'], "Salut $pseudo, ton compte vient d'etre cree", $message);
        
    /* END add user ! */
    
    redirect("/p/admin/?tab=users#admin_nav");
}

/* END form management here */

if(!isset($_from_index)){
    die("Not meant to be accessed directly.");
}


$tab = "summary";
if(isset($_REQUEST['tab'])){
    $tab = $_REQUEST['tab'];
}

?>
<h2>Console d'administration</h3>
<p>Ici, vous pouvez administrer l'ensemble du serveur, comme par exemple : voir les utilisateurs, en ajouter, en enlever, voir les pages, etc.</p>
<ul class="nav nav-pills" id="admin_nav">
    <li class="nav-item">
        <a class="nav-link <?php if($tab=='summary' or $tab==''){echo 'active';} ?>" href="?tab=summary">Résumé</a>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#admin_nav" role="button" aria-haspopup="true" aria-expanded="false">BDD</a>
        <div class="dropdown-menu">
            <a class="dropdown-item <?php if($tab=='users'){echo 'active';} ?>" href="?tab=users#admin_nav">Utilisateurs</a>
            <a class="dropdown-item <?php if($tab=='groups'){echo 'active';} ?>" href="?tab=groups#admin_nav">Groupes</a>
            <a class="dropdown-item <?php if($tab=='pages'){echo 'active';} ?>" href="?tab=pages#admin_nav">Pages</a>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php if($tab=='animes'){echo 'active';} ?> disabled" href="?tab=animes#admin_nav">Animés</a>
    </li>
</ul>
<div class="jumbotron">
<?php

switch($tab) {
    
    case 'users':
        $orderby = "id";
        if(isset($_GET['sort'])){
            $orderby = valid_identifier($_GET['sort'], $orderby, "users");
        }

        /* BEGIN get users list */

        $pdo = connect();
            
        $request = "SELECT * FROM users ORDER BY $orderby DESC";
        $stmt = $pdo->prepare($request);
        $stmt->execute();
        $users = $stmt->fetchAll();
        //print_r($users);

        /* END get users list */
        ?>
        <form method="post" action="/admin.php?redirect=clean&amp;tab=mod_user">
        <?php make_table("Utilisateurs", $users, ['password']); ?>
        <input type="submit" class="btn btn-outline-secondary" value="Usurper" name="impersonate"/>
        <a href="?tab=create_user" class="btn btn-outline-primary">Créer un utilisateur</a>
        <input type="submit" class="btn btn-outline-primary" name="modify" value="Modifier utilisateur"/>
        </form>
        <?php
        break;
    case 'mod_user':
        $pdo = connect();
            
        $request = "SELECT * FROM users WHERE id=:id";
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
            ":id"=>$_POST['select']
        ));
        $user = $stmt->fetch();
        
        $request = "SELECT * FROM groups";
        $stmt = $pdo->prepare($request);
        $stmt->execute();
        $groupes = $stmt->fetchAll();
        
        ?>
        <form action="/admin.php?mod=1" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="pseudo">Pseudo</label>
                    <input type="text" class="form-control" id="pseudo" placeholder="Pseudo" name="pseudo" value="<?php echo $user['pseudo']; ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="Email" name="email" value="<?php echo $user['email']; ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="password">Mot-de-passe</label>
                    <input type="password" class="form-control disabled" id="password" placeholder="Password" name="password" minlength="8">
                </div>
                <div class="form-group col-md-6">
                    <label class="form-control-label" for="groupe">
                        Groupe
                    </label>
                    <select class="form-control" id="groupe" name="groupe" required>
                        <?php foreach($groupes as $groupe){
                        $groupe = queryFilter($groupe);
                        ?>
                            <option <?php if($groupe['name'] == $user['groupe']) { echo 'selected="selected"'; }?> ><?php echo $groupe['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <input type="submit" class="btn btn-primary" value="Modifier" name="modify"/>
            </div>
            <input type="hidden" style="display=none;" name="id" value="<?php echo $user['id']; ?>" />
        </form>
        <?php
        
        break;
    case 'create_user':
        ?>
        <form action="/admin.php" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="pseudo">Pseudo</label>
                    <input type="text" class="form-control" id="pseudo" placeholder="Pseudo" name="pseudo" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="Email" name="email">
                </div>
                <div class="form-group col-md-6">
                    <label for="password">Mot-de-passe</label>
                    <input type="password" class="form-control" id="password" placeholder="Password" name="password" minlength="8">
                </div>
            </div>
            <div class="form-group">
                <div class="form-check">
                <input class="form-check-input" type="checkbox" id="user_is_admin" name="groupe" value="admin">
                <label class="form-check-label" for="user_is_admin">
                    Admin
                </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
        <?php
        break;
    case 'pages':

        /* BEGIN get pages list */
        
        $pdo = connect();
        
        $orderby = "name";
        if(isset($_GET['sort'])){
            $orderby = valid_identifier($_GET['sort'], $orderby, "pages");
        }
            
        $request = "SELECT * FROM pages ORDER BY $orderby ASC";
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
        ));
        $pages = $stmt->fetchAll();

        /* END get pages list */
        
        make_table("Pages", $pages, ['content']);
        break;
    case 'groups':
        /* BEGIN get groups list */
        
        $pdo = connect();
        
        $orderby = "name";
        if(isset($_GET['sort'])){
            $orderby = valid_identifier($_GET['sort'], $orderby, "groups");
        }
            
        $request = "SELECT * FROM groups ORDER BY $orderby ASC";
        $stmt = $pdo->prepare($request);
        $stmt->execute(array(
        ));
        $groupes = $stmt->fetchAll();

        /* END get groups list */
        
        make_table("Groupes", $groupes, []);
        break;
    case 'summary':
        /* BEGIN get summary */

        $pdo = connect();
            
        $request = "SELECT COUNT(*) FROM users";
        $stmt = $pdo->prepare($request);
        $stmt->execute();
        $nbusers = $stmt->fetch()[0];
        
        $request = "SELECT COUNT(*) FROM pages";
        $stmt = $pdo->prepare($request);
        $stmt->execute();
        $nbpages = $stmt->fetch()[0];
        
        $request = "SELECT COUNT(*) FROM animes";
        $stmt = $pdo->prepare($request);
        $stmt->execute();
        $nbanimes = $stmt->fetch()[0];
        
        $request = "SELECT COUNT(*) FROM anime_episodes";
        $stmt = $pdo->prepare($request);
        $stmt->execute();
        $nbanime_episodes = $stmt->fetch()[0];

        /* END get summary */
        ?>
        <p>Il y a :</p>
        <ul>
            <li><?php echo $nbusers;?> utilisateurs</li>
            <li><?php echo $nbpages;?> pages (dans la base de donnée)</li>
            <li><?php echo $nbanimes;?> animés, avec <?php echo $nbanime_episodes;?> épisodes au total</li>
        </ul>
        <?php
        
        break;
    default:
        ?>
        <em>Oh oh... on dirait que cet onglet n'existe tout bonnement pas !</em>
<?php
}
?>
</div>
