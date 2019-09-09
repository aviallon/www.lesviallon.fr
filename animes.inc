<?php
include_once("db.inc");
$pdo = connect();

if (isset($_REQUEST['anime_id']) and !isset($_REQUEST['ep'])){
    $request = "SELECT A.name AS anime_name, A.categories, E.name AS episode, E.number, E.files FROM animes AS A JOIN anime_episodes AS E ON E.anime_id = A.id WHERE A.id=:animeid ORDER BY E.number DESC";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":animeid"=>$_REQUEST['anime_id']
    ));
    
    $episodes = $stmt->fetchAll();
    
    if(count($episodes) == 0){
        echo "Ooops, no episodes for that anime. Does it even exist ?";
    } else {
        echo '<h2>'.$episodes[0]['anime_name'].'</h2>';
        echo '<ul class="epsode-list">';
        foreach($episodes as $episode){
            echo '<li><b>['.$episode['number'].']</b> : "'.$episode['episode'].'" <a href="?anime_id='.$_REQUEST['anime_id'].'&ep='.$episode['number'].'"><span class="oi oi-eye"></span> Watch</a></li>';
        }
        echo '</ul>';
    }
} else if (isset($_REQUEST['anime_id']) and isset($_REQUEST['ep'])){
    $request = "SELECT A.name AS anime_name, A.categories, E.name AS episode, E.number, E.files FROM animes AS A JOIN anime_episodes AS E ON E.anime_id = A.id WHERE E.number=:epnum AND A.id=:animeid";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":epnum"=>$_REQUEST['ep'],
        ':animeid'=>$_REQUEST['anime_id']
    ));
    
    $episode = $stmt->fetch();
    
    if(!$episode){
        echo "Oops, this episode doesn't seem to exist !";
    } else {
        echo '<h2><a class="unstyled-link" href="?anime_id='.$_REQUEST['anime_id'].'">'.$episode['anime_name'].'</a></h2>';
        echo '<h3>'.$episode['episode'].'</h3>';
        $files = array_db($episode['files']);
    ?>
    <a class="" href="/animes/<?php echo $files[0]; ?>" target="_blank"><span class="oi oi-data-transfer-download" aria-hidden="true"></span> Télécharger l'épisode</a>
    <div class="video-wrapper">
    <video id="anime-video" class="video-js vjs-big-play-centered vjs-default-skin" preload="auto" width="640" height="264" data-setup='{ "playbackRates": [1, 1.12, 2] }' controls>
    <?php
        foreach($files as $file){
            $vid_type = '';
            $matches = [];
            preg_match('/.*\.([a-zA-Z0-9]{3,})/', $file, $matches);
            if(count($matches) >= 2){
                switch($matches[1]){
                    case 'webm':
                        $vid_type = 'webm';
                        break;
                    case 'mp4':
                        $vid_type = 'mp4';
                        break;
                    case 'mkv':
                        $vid_type = 'mp4';
                        break;
                    default:
                        $vid_type = 'mp4';
                }
                echo "<source src=\"/animes/$file\" type=\"video/$vid_type\">\n";
            }
        }
        ?>
        Your browser is not HTML5 compatible... what a shame !
        </video>
        </div>
        <script type="text/javascript">
            var player = videojs('anime-video');
            //player.bitrateGraph({bitrates: [200, 3, 3.5, 5, 6, 7, 8, 4, 5, 3]});
        </script>
        <?php
    }
} else {
    $request = "SELECT A.id AS anime_id, A.name AS anime_name, A.categories, COUNT(*) AS n_eps FROM animes AS A JOIN anime_episodes AS E ON E.anime_id = A.id GROUP BY A.id";
    $stmt = $pdo->prepare($request);
    $stmt->execute();

    while($anime = $stmt->fetch()){
        echo '<h3><a class="anime-name" href="?anime_id='.$anime['anime_id'].'">'.$anime['anime_name']."</a></h3>";
        $categories = array_db($anime['categories']);
        echo "<em>Catégories :</em> ";
        foreach($categories as $i => $category) {
            echo "<b>".$category."</b>";
            if($i+1 < count($categories)){
                echo " ; ";
            }
        }
        echo "<br/>";
        echo "</ul>";
        echo "<em>Nombre d'épisodes : </em>".$anime['n_eps'];
        echo "<br />";
    }
}

?>
