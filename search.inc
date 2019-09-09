<?php
include_once("db.inc");
include_once("utility.inc");

if(!isset($_GET['q'])){
    echo "Recherchez quelque chose !";
} else if(strlen($_GET['q']) < 2){
    echo "Pas assez de caractères de recherche.";
} else if(!preg_match("/^\w+$/", $_GET['q'])){
    echo "Veuillez n'utiliser que des caractères alphanumériques.";
} else {
?>

<p>Résultats de la recherche pour <code><?php echo $_GET['q']; ?></code> : </p>
<?php
    $pdo = connect();
    $request = "SELECT * FROM pages AS P WHERE (P.hidden = false OR P.edit_level <= :userlevel OR P.author_id = :userid) AND P.content LIKE '%' || :req || '%' ORDER BY P.date DESC LIMIT 20";
    $stmt = $pdo->prepare($request);
    $stmt->execute(array(
        ":userlevel"=>$_SESSION['edit_level'],
        ":userid"=>$_SESSION['id'],
        ":req"=>$_GET['q']
    ));
    $results = $stmt->fetchAll();
    
    foreach($results as $res){
        $matches = [];
        $content = searchBbcodeStrip($res['content']);
        //$content = bbcode($res['content']);
        $find = preg_quote(htmlspecialchars($_GET['q']), "/");
        //var_dump($find);
        preg_match_all("/".$find."/si", $content, $matches, PREG_OFFSET_CAPTURE |  PREG_PATTERN_ORDER);
        //$content = strip_tags($content);
        
    ?>
        <h4><?php echo $res['title'];?></h4>
    <?php
        $found_it = 0;
        foreach($matches[0] as $match){
            $found_it++;
            $before = substr($content, 0, $match[1]);
            $after = substr($content, $match[1]+strlen($match[0]), -1);
            //$found = substr($before, -30)."<a href='/p/".$res['name']."?marker=".$match[1].",".strlen($match[0])."#mark' title='Voir dans l&#39;arcticle'><b>".$match[0]."</b></a>".substr($after,0, 50);
            $found = substr($before, -30)."<a href='/p/".$res['name']."?q=".$find."#mark".$found_it."' title='Voir dans l&#39;arcticle'><b>".$match[0]."</b></a>".substr($after,0, 50);
            
            echo "<div>...".trim($found)."...</div>";
        }
    }
}
?>
