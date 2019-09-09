<?php
include_once("utility.inc");
include_once("db.inc");


if(isset($_POST['reset_token'])){
    if( ($_POST['password'] != $_POST['passwordVerif']) or (strlen($_POST['password']) < 8)){
        $_SESSION['error'] = 'password_non_conform';
        redirect('/p/user/?reset='.$_POST['reset_token']);
    }
    $password = $_POST['password'];
    $password_hash = sha512_salted($password, $_POST['pseudo']);
    
    $pdo = connect();
    
    $request = "SELECT * FROM users WHERE reset_token = :resttkn";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":resttkn"=>$_POST['reset_token']
    ));
    $user = $stmt->fetch();
    
    
    $request = "UPDATE users SET password = :pass, reset_token = '' WHERE reset_token = :resttkn";
    $stmt = $pdo->prepare($request);
    $success = $stmt->execute(array(
        ":pass"=>$password_hash,
        ":resttkn"=>$_POST['reset_token']
    ));
    login($user['id'], $user['pseudo'], $user['groupe'], $user['edit_level']);
    
    if($success){
        $_SESSION['reset_success'] = $success;
    } else {
        $_SESSION['reset_success'] = $success;
    }
    
    redirect('/p/user');
}

if(isset($_GET['reset_passwd'])){
    reset_password($_SESSION['id']);
    logout();
    redirect('/p/home');
}

if(!isset($_from_index)){
    die("Not meant to be accessed directly.");
}

if(isset($_GET['reset'])){
    logout(); // Disconnect any previously connected user.

    $pdo = connect();
    
    $request = "SELECT * FROM users WHERE reset_token=:tkn";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":tkn"=>$_GET['reset']
    ));
    $user = $stmt->fetch();
    
    $bad_password = false;
    if(isset($_SESSION['error'])){ if($_SESSION['error'] == "password_non_conform") { $bad_password = true; }}
    ?>
    <form action="/user.php" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="pseudo">Pseudo</label>
                <input type="text" class="form-control" id="pseudo" value="<?php echo $user['pseudo'] ?>" name="pseudo" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" value="<?php echo $user['email'] ?>" name="email" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="password">Mot-de-passe</label>
                <input type="password" class="form-control <?php if($bad_password){ echo "is-invalid"; } ?>" id="password" placeholder="Password" name="password" minlength="8" required>
                <div class="invalid-feedback">
                Mot de passe trop court ou bien les mots de passe ne correspondent pas.
                </div>
            </div>
             <div class="form-group col-md-6">
                <label for="password">Vérification</label>
                <input type="password" class="form-control" id="password-verif" placeholder="Type again" name="passwordVerif" minlength="8" required>
            </div>
        </div>
        <input type="hidden" style="display:none;" value="<?php echo $_GET['reset']; ?>" name="reset_token">
        <button type="submit" class="btn btn-primary">Mise-à-jour</button>
    </form>
    <script type="text/javascript">
        var password = document.getElementById("password"),
            confirm_password = document.getElementById("password-verif");

        function validatePassword(){
            if(password.value != confirm_password.value) {
                confirm_password.setCustomValidity("Mot de passe pas identique");
            } else {
                confirm_password.setCustomValidity('');
            }
        }

        password.onchange = validatePassword;
        confirm_password.onkeyup = validatePassword;
    </script>
<?php
}

if(isset($_SESSION['reset_success'])){ 
    if($_SESSION['reset_success']){ ?>
    <div class="alert alert-success" role="alert">Réussite ! Vous pouvez maintenant vous connecter avec vos identifiants</div>
<?php } else { ?>
     <div class="alert alert-danger" role="alert">Erreur dans la requête ! Veuillez réessayer ou contacter un administrateur.</div>
<?php }
    unset($_SESSION['reset_success']);
} else if(!isset($_GET['reset'])){

if(isset($_GET['user'])){
    $pdo = connect();
    
    $request = "SELECT pseudo, groupe, crea_time FROM users WHERE pseudo = :name";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":name"=>$_GET['user']
    ));
    $user = $stmt->fetch();
    
    if(!empty($user)){

        $creation_date = strftime('%e %B %Y à %T', strtotime(str_replace('-', '/', $user['crea_time'])));
?>

        <h2>Page utilisateur</h2>
        <p>Page de : <b><?php echo $user['pseudo']; ?></b>, qui est un <b><?php echo $user['groupe']; ?></b>.</p>
        <p>Il/Elle a créé son compte le <?php echo $creation_date ?>.</p>

<?php } else { ?>
        <h2>Utilisateur introuvable</h2>
<?php }
} else {

    $pdo = connect();
    
    $request = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":id"=>$_SESSION['id']
    ));
    $user = $stmt->fetch();

    $creation_date = strftime('%e %B %Y à %T', strtotime(str_replace('-', '/', $user['crea_time'])));
    
?>

<h2>Page utilisateur</h2>
<?php if($_SESSION['signed_in']) { ?>
<p>Bienvenue sur votre page <b><?php echo $_SESSION['pseudo']; ?></b></p>
<p>Groupe : <?php echo $_SESSION['groupe'];?>, niveau d'édition : <?php echo $_SESSION['edit_level'];?>.</p>
<p>Vous avez créé votre compte le <?php echo $creation_date ?>.</p>
<a href="/user.php?reset_passwd=1" title="Demander une réinitialisation du mot de passe" class="btn btn-warning">Réinitialiser mot-de-passe</a>
<?php } else { ?>
<p>Vous n'êtes pas connecté !</p>
<?php } 
    } 
}
?>
