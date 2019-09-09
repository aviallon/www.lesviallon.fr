<?php
if(!isset($_from_index)){
    die("You are not supposed to access this file directly.");
}?>
<header class="blog-header py-3">
    <div class="row flex-nowrap justify-content-center align-items-center">
        <div class="text-center">
            <span class="blog-header-logo text-dark display-4">Les</span>
            <span class="ml-2 blog-header-logo text-dark display-4 name">Viallon</span>
        </div>
    </div>
</header>

<nav class="navbar navbar-expand-md bg-dark navbar-dark <?php /*sticky-top*/ ?>">
    <a class="navbar-brand" href="#">Les Viallon</a>
    
    <!-- Toggler/collapse Button -->
    <div class="d-flex justify-content-end">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <?php if($_SESSION['signed_in']) { ?>
        <div class="ml-1 d-sm-block d-md-none my-auto">
            <?php echo createUserLink($_SESSION['pseudo'], false, true); ?>
            <!--<a class="unstyled-link" href="/p/user">
                <div class="user-icon" style="<?php echo colorFromCar($_SESSION['pseudo'][0]); ?>">
                    <span class="unselectable"><?php echo $_SESSION['pseudo'][0] ?></span>
                </div>
            </a>-->
        </div>
        <?php } ?>
        
        <div class="ml-1 d-sm-block d-md-none my-auto">
            <div class="align-items-center">
                <?php if(!$_SESSION['signed_in']) { ?>
                <a class="btn btn-outline-secondary <?php if($_SESSION['page'] == 'login'){echo 'disabled';} ?>" href="/p/login">
                <span class="oi oi-account-login"></span>
                </a>
                <?php } else { ?>
                <a class="btn btn-outline-danger" href="/login.php?disconnect=1" title="Se déconnecter"><span class="oi oi-account-logout"></span></a>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
        <ul class="navbar-nav">
            <li class="nav-item <?php if($_SESSION['page'] == 'home'){echo 'active';} ?>">
                <a class="nav-link" href="https://www.lesviallon.fr/">Home</a>
            </li>
            <li class="nav-item <?php if($_SESSION['page'] == 'blog'){echo 'active';} ?>">
                <a class="nav-link" href="/p/blog">Blog</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://nextcloud.lesviallon.fr/">Nextcloud</a>
            </li>
            <?php if($_SESSION['signed_in']) { ?>
            <li class="nav-item <?php if($_SESSION['page'] == 'animes'){echo 'active';} ?>">
                <a class="nav-link" href="/p/animes/">Animés</a>
            </li>
            <?php } ?>
            <li class="nav-item <?php if($_SESSION['page'] == 'about'){echo 'active';} ?>">
                <a class="nav-link" href="/p/about">&Agrave; propos</a>
            </li>
        </ul>
        <form class="form-inline my-2 my-md-0 ml-auto" action="/p/search" method="get">
            <div class="input-group mr-sm-2">
                <input class="form-control input-search" type="text" placeholder="Search" aria-label="Search" name="q" minlength="2" value="<?php echo $search_req; ?>">
                <div class="input-group-append">
                    <button class="btn btn-success" type="submit">Search</button>
                </div>
            </div>
        </form>
        <div class="d-none d-md-block ">
            <div class="d-flex justify-content-end align-items-center">
                <?php if(!$_SESSION['signed_in']) { ?>
                <a class="btn btn-outline-secondary <?php if($_SESSION['page'] == 'login'){echo 'disabled';} ?>" href="/p/login">Se connecter</a>
                <?php } else { ?>
                <span class="text-light mx-2"><a class="unstyled-link" href="/p/user"><?php echo $_SESSION['pseudo']; ?></a></span>
                <a class="btn btn-outline-danger" href="/login.php?disconnect=1" title="Se déconnecter">Déconnexion</a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>
