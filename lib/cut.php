<?php namespace ffta_extractor;

define("DEBUG", false);

class CUT
{

    private $_configuration;
    private $_bdd;

    public function __construct( $configuration, $bdd )
    {
        $this->_configuration = $configuration;
        $this->_bdd = $bdd;
    }

    public function ingestScoreInBDD( $stmt, $values, &$cpt_entree, $cpt_ligne ){
  		$cpt = 1;
  		foreach( $values as $val ){ 
  			$stmt->bindValue($cpt, $val);
  			$cpt++;
  		}
  	
  		try{
  			$result = $stmt->execute();
        if(DEBUG) echo "|  |  |  result = $result<br/>\n";
  			$cpt_entree ++;
  		}catch (\PDOException $e){
  			echo "|  |  |  Erreur d'insetion de la ligne : $cpt_ligne";
  			echo " : ".$e->getMessage( )."<br/>\n";
  		}
  	}
	
    public function fill_all_results( $csvUrl ){
        

        echo "|  |  Parsing du fichier - <br/>\n";
        $query_prepare = "INSERT INTO RESULTS VALUES(:1";
        for($i = 2; $i <= 42; $i++){
            $query_prepare .= ", :".$i;
        }
        $query_prepare .= ")";
        
        $stmt = $this->_bdd->get_PDO()->prepare($query_prepare);
        
        $file = fopen ($csvUrl, "r");

        if(DEBUG) echo "|  |  |  URL = $csvUrl<br/>\n";
        
        # drop first line
        $line = fgets ($file);

        $cpt_ligne = 0;
        $cpt_entree = 0;
        while (!feof ($file)) {
            $line = fgets ($file);

            $corrected_line = str_replace("\"", "", $line);
            $corrected_line = iconv('ISO-8859-1','UTF-8//TRANSLIT', $corrected_line);
            if(DEBUG) echo "|  |  |  $corrected_line<br/>\n";
            
			
            $values = explode(";", $corrected_line);


      			// Cas particulier du 2X25M + 2X18M
      			if( $values[24] == "2X25M + 2X18M" && $values[31] != "0" ){
      				$values[24] = "2X25M";
      				$values[13] = strval(intval($values[29]) + intval($values[30]));
      				$this->ingestScoreInBDD($stmt, $values, $cpt_entree, $cpt_ligne);
      				
      				$values[24] = "2X18M";
      				$values[41] = strval(intval($values[41])+1);
      				$values[13] = strval(intval($values[31]) + intval($values[32]));
      				$this->ingestScoreInBDD($stmt, $values, $cpt_entree, $cpt_ligne);
      			} else if( $values[24] == "2X25M + 2X18M" ){
      				if ( intval($values[41]) % 2 == 1 ) {
      				  $values[24] = "2X25M";
              } else {
      				  $values[24] = "2X18M";
              }
      				$this->ingestScoreInBDD($stmt, $values, $cpt_entree, $cpt_ligne);
      			} else {
      				$this->ingestScoreInBDD($stmt, $values, $cpt_entree, $cpt_ligne);
      			}

      			$cpt_ligne ++;
        }
        fclose($file);


        echo "|  |  Fin de parsing du fichier - \n";
        echo "OK : ".  strval($cpt_ligne)." lignes - ".  strval($cpt_entree)." entrée </br>\n";
    }

    // fonction de comparaison utilisé pour les égalité
    private static function cmp($a, $b) {

        $scoresa = array();
        foreach($a["SCORES"] as $score_row)
            $scoresa[] = intval($score_row['SCORE']);
        $scoresb = array();
        foreach($b["SCORES"] as $score_row)
            $scoresb[] = intval($score_row['SCORE']);

        while(count($scoresa) > 0 && count($scoresb) > 0){
            $scorexa = array_shift($scoresa);
            $scorexb = array_shift($scoresb);
            if( $scorexa > $scorexb ){
                return 0;
            }
            if( $scorexa < $scorexb ){
                return 1;
            }
        }

        if (count($scoresa) == 0 && count($scoresb) == 0) {
            return (strcmp($a["NO_LICENCE"],$b["NO_LICENCE"]) > 0);
        }

        if( count($scoresa) == 0 ){
            return 1;
        }
        if( count($scoresb) == 0 ){
            return 0;
        }
        throw new \Exception("Echec du trie du tableau d'egalite");
    }

    private function fill_cut( $cut_name ){
        

        $table_name = $this->_bdd->get_table_cut_name($cut_name);
        echo "|  |  Creation de la table $table_name - \n";
        $this->_bdd->create_table_cut($cut_name, false);
        echo "OK </br>\n";

        $pdo = $this->_bdd->get_PDO();
        $nb_score = $this->_configuration->get_configuration_cut($cut_name, "nb_score");
        

        //----------------------------
        // STEP 1 : Création sous selection
        //----------------------------
        #$querySubSelect = "NUM_DEPART='1' ";
        $querySubSelect = "'1' ";
        
        // Discipline
        $discipline = $this->_configuration->get_configuration_cut($cut_name, "discipline");
        $querySubSelect .= $this->_bdd->create_and_cond_array($discipline, "DISCIPLINE");
        
        // Forule
        $formule = $this->_configuration->get_configuration_cut($cut_name, "formule");
        $querySubSelect .= $this->_bdd->create_and_cond_array($formule, "FORMULE_TIR");

        // Sexe
        $sexe = $this->_configuration->get_configuration_cut($cut_name, "sexe");
        $querySubSelect .= $this->_bdd->create_and_cond_array($sexe, "SEXE_PERSONNE");
        
        // Categorie
        $categorie = $this->_configuration->get_configuration_cut($cut_name, "categorie");
        $querySubSelect .= $this->_bdd->create_and_cond_array($categorie, "CAT");
        
        // Arme
        $arme = $this->_configuration->get_configuration_cut($cut_name, "arme");
        $querySubSelect .= $this->_bdd->create_and_cond_array($arme, "ARME");
        

        //----------------------------
        // STEP 2 : Extraire la liste des archers
        //----------------------------
        echo "|  |  Extraction de la liste des archer - \n";

        $query = "SELECT NO_LICENCE, NOM_PERSONNE, PRENOM_PERSONNE , NOM_STRUCTURE
        FROM RESULTS  
        WHERE  ".$querySubSelect." GROUP BY NO_LICENCE";
        
        if(DEBUG) echo "|  |  |  DEBUG : requete archers query </br>\n";

        $sth_archer = $pdo->prepare($query);
        $sth_archer->execute();
        
        $result = $sth_archer->fetchAll();
        echo "OK : ".  strval(count($result))." </br>\n";

        echo "|  |  Calcul des scores des archers - </br>\n";

        $sth_insert = $pdo->prepare("INSERT INTO $table_name (NO_LICENCE, NOM_PERSONNE, PRENOM_PERSONNE, CLUB, SCORES, SCORE_TOTAL) VALUES (:NO_LICENCE, :NOM_PERSONNE, :PRENOM_PERSONNE, :CLUB, :SCORES, :SCORE_TOTAL)");
        $sth_update = $pdo->prepare("UPDATE $table_name SET SCORE_TOTAL=:SCORE_TOTAL, SCORES=:SCORES WHERE NO_LICENCE=:NO_LICENCE");
        $sth_score = $pdo->prepare("SELECT SCORE FROM RESULTS WHERE  $querySubSelect AND NO_LICENCE=:NO_LICENCE ORDER BY SCORE DESC");
        $sth_score_exist = $pdo->prepare("SELECT SCORE_TOTAL FROM $table_name WHERE NO_LICENCE=:NO_LICENCE");
        
        foreach ($result as $archer){
            //----------------------------
            // STEP 3 : Calcul du score + ajout de l'archer
            //----------------------------
            $sth_score->bindValue(":NO_LICENCE", $archer["NO_LICENCE"]);
            $sth_score->execute();
            
            if(DEBUG) echo "|  |  |  DEBUG : Calcul du score de ".$archer["NO_LICENCE"]." - ";

            $scores = array ();

            $result = $sth_score->fetchAll();
            $score_total = 0;
            for($i = 0; $i < $nb_score; $i++){
                if( $i < count($result) ){
                    array_push($scores, $result[$i]["SCORE"] );
                    $score_total += $result[$i]["SCORE"];
                    if(DEBUG) echo strval($result[$i]["SCORE"]).", ";
                }
            }
            $score_total /= $nb_score;
            if(DEBUG) echo " = ".strval($score_total)." </br>\n";

            // Check de l'existance d'un score
            $sth_score_exist->bindValue(":NO_LICENCE", $archer["NO_LICENCE"]);
            $sth_score_exist->execute();
            
            if( count ($sth_score_exist->fetchAll()) > 0 ){
                // Update
                $sth_update->bindValue(":NO_LICENCE", $archer["NO_LICENCE"]);
                $sth_update->bindValue(":SCORE_TOTAL", $score_total);
                $sth_update->bindValue(":SCORES", implode(",", $scores));
                try{
                    $sth_update->execute();
                }catch (\PDOException $e){
                    echo "|  |  |  Echec de l'a mise a jour du nouveau score de ".$archer["NO_LICENCE"]." dans ".$cut_name." : ".$e."<br/>\n";
                }
            } else {
                // Insert
                $sth_insert->bindValue(":NO_LICENCE", $archer["NO_LICENCE"]);
                $sth_insert->bindValue(":NOM_PERSONNE", $archer["NOM_PERSONNE"]);
                $sth_insert->bindValue(":PRENOM_PERSONNE", $archer["PRENOM_PERSONNE"]);
                $sth_insert->bindValue(":CLUB", $archer["NOM_STRUCTURE"]);
                $sth_insert->bindValue(":SCORES", implode(",", $scores));
                $sth_insert->bindValue(":SCORE_TOTAL", $score_total);
                
                try{
                    $sth_insert->execute();
                }catch (\PDOException $e){
                    echo "|  |  |  Echec de l'insert du nouveau score de ".$archer["NO_LICENCE"]." dans ".$cut_name." : ".$e."<br/>\n";
                }
            }
        } 

        echo "|  |  Find de calcul des scores des archers - OK</br>\n";

        //----------------------------
        // STEP 4 : Calcul Rank
        //----------------------------
        $query_order_by_score = "SELECT NO_LICENCE
        FROM $table_name  
        ORDER BY SCORE_TOTAL DESC";

        echo "|  |  Calcul du rank des archers - \n";

        $sth_order_by_score = $pdo->prepare($query_order_by_score);
        $sth_order_by_score->execute();
        
        $result = $sth_order_by_score->fetchAll();

        $sth_update_rank = $pdo->prepare("UPDATE $table_name SET RANK=:RANK WHERE NO_LICENCE=:NO_LICENCE");
        
        $cpt = 1;
        foreach ($result as $archer){

            $sth_update_rank->bindValue(":RANK", $cpt);
            $sth_update_rank->bindValue(":NO_LICENCE", $archer["NO_LICENCE"]);
            $sth_update_rank->execute();

            $cpt ++;
        }
        echo "OK </br>\n";
        
        //----------------------------
        // STEP 5 : Gestion égalité
        //----------------------------
        $query_egalite = "SELECT T1.SCORE_TOTAL as SCORE  FROM $table_name AS T1, (SELECT * FROM $table_name) AS T2 WHERE T1.SCORE_TOTAL= T2.SCORE_TOTAL AND T1.NO_LICENCE!=T2.NO_LICENCE GROUP BY SCORE ORDER BY SCORE DESC";

        echo "|  |  Recherche des égalité - \n";

        $sth_egalite = $pdo->prepare($query_egalite);
        $sth_egalite->execute();
        
        $sth_get_archer_egalite = $pdo->prepare("SELECT NO_LICENCE, RANK FROM $table_name WHERE  SCORE_TOTAL=:SCORE_TOTAL ORDER BY RANK ASC");

        $result_scores = $sth_egalite->fetchAll();

        echo "OK : ".  strval(count($result_scores))." </br>\n";
        foreach ($result_scores as $score){

            // Récupération des archer en égalité
            $sth_get_archer_egalite->bindValue(":SCORE_TOTAL", $score["SCORE"]);
            $sth_get_archer_egalite->execute();

            $sth_get_scores = $pdo->prepare("SELECT SCORE FROM RESULTS WHERE  ".$querySubSelect." AND NO_LICENCE=:NO_LICENCE ORDER BY SCORE DESC");


            $data_trie = array();

            // Pour chaque archer en égalité
            $result_archer_score = $sth_get_archer_egalite->fetchAll();

            foreach ($result_archer_score as $archer){
                
                // Récupération de l'ensemble des score de l'archer :
                $sth_get_scores->bindValue(":NO_LICENCE", $archer["NO_LICENCE"]);
                $sth_get_scores->execute();

                $result_score_archer = $sth_get_scores->fetchAll();

                //$data_trie[] = array( "NO_LICENCE"=>$archer["NO_LICENCE"],
                //    "SCORES"=>array_slice($result_score_archer, $nb_score) );
                $data_trie[] = array( "NO_LICENCE"=>$archer["NO_LICENCE"],
                    "SCORES"=>$result_score_archer );

            }
            // Trie des archer
            uasort($data_trie, 'ffta_extractor\CUT::cmp');

            // Récuperation du plus petit rank
            $rank_min = $result_archer_score[0]["RANK"];

            // Mise a jour des ranks
            $sth_update_rank = $pdo->prepare("UPDATE $table_name SET RANK=:RANK WHERE NO_LICENCE=:NO_LICENCE");

            foreach ($data_trie as $archer){

                $sth_update_rank->bindValue(":RANK", $rank_min);
                $sth_update_rank->bindValue(":NO_LICENCE", $archer["NO_LICENCE"]);
                $sth_update_rank->execute();

                $rank_min++;
            }

        }
    }

    public function fill_all_cuts( ){
        foreach( $this->_configuration->get_configuration_cut_names() as $cut_name ){
            echo "|  Calcul du cut - $cut_name - </br>\n";
            $this->fill_cut( $cut_name );
            echo "|  |  OK </br>\n";
        }
    }
}

?>
