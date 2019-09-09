 <?php
include_once("utility.inc");
include_once("db.inc");

 if(isset($_GET['disconnect'])){
    logout();
    redirect('/p/login');
 }

 if(isset($_POST['user']) and isset($_POST['pwd'])){
 
    $user = trim($_POST['user']);
    $passwd = sha512_salted($_POST['pwd'],$_POST['user']);
    
    $pdo = connect();
    
    $request = "SELECT users.*, G.edit_level FROM users JOIN groups AS G ON G.name = users.groupe WHERE pseudo=:u";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":u"=>$user
    ));
    $row = $stmt->fetch();
    
    if ($row) {
        // Users exists, check password
        if($row['password'] == $passwd){
            login($row['id'], $user, $row['groupe'], $row['edit_level']);
            // Redirect to last page
            redirect('/p/home');
        } else { // Wrong password
            $_SESSION['signed_in'] = false;
            $_SESSION['pseudo'] = $user;
            $_SESSION['login_error'] = "wrong_password";
            
            // TODO : Add failed attempts counter
            redirect('/p/login');
        }
    } else { // User not found !
        $_SESSION['signed_in'] = false;
        $_SESSION['pseudo'] = "null";
        $_SESSION['login_error'] = "wrong_user";
        redirect('/p/login');
    }
    die;
}

$wrong_user = false;
if(isset($_SESSION['login_error'])){ if($_SESSION['login_error'] == "wrong_user") { $wrong_user = true; }}

$wrong_password = false;
if(isset($_SESSION['login_error'])){ if($_SESSION['login_error'] == "wrong_password") { $wrong_password = true; }}
?>
 <form method='post' action="/login.php">
 <div class="form-group">
  <label for="usr">Pseudo:</label>
  <input type="text" class="form-control <?php if($wrong_user){ echo "is-invalid"; } ?>" id="usr" name="user" required v-model="user.name" maxlength="255" value="<?php if($wrong_password){ echo $_SESSION['pseudo'] ; } ?>" >
  <div class="invalid-feedback">
    Utilisateur introuvable
  </div>
</div>
<div class="form-group">
  <label for="pwd">Mot de passe:</label>
  <input type="password" class="form-control <?php if($wrong_password){ echo "is-invalid"; } ?>" id="pwd" name="pwd" required v-model="user.password" maxlength="2048">
  <div class="invalid-feedback">
    Mot de passe erroné.
  </div>
</div>
<input type="submit" class="btn btn-outline-primary" value="Connexion">
</form>
