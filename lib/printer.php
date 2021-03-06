<?php namespace ffta_extractor;

class Printer
{

    private $_configuration;
    private $_bdd;
    private $_export;
    private $_logger;

    public function __construct( $configuration, $bdd, $export, $logger, $request )
    {
        $this->_configuration = $configuration;
        $this->_bdd = $bdd;
        $this->_export = $export;
        $this->_request = $request;
        $this->_logger = $logger;
    }

    public function print_cut( $cut_name, $div=false, $export=true, $admin=false, $inscription=false, $print_param="" ){

        // Update status archer
        if( $admin ){
            $this->update_status_admin();
        } else {
            $this->update_status();
        }

        // Récupération des données
        $pdo = $this->_bdd->get_PDO();
        $table_name = $this->_bdd->get_table_cut_name($cut_name);
        $stmt = $pdo->prepare("SELECT * FROM $table_name ORDER BY RANK ASC");
        $stmt->execute();
        $result = $stmt->fetchAll();

        $taille_cut = $this->_configuration->get_configuration_cut($cut_name, "taille");

        $nb_preinscrit = $pdo->query("SELECT COUNT(*) FROM $table_name WHERE ETAT='1'")->fetchColumn();
        $nb_inscrit = $pdo->query("SELECT COUNT(*) FROM $table_name WHERE ETAT='2'")->fetchColumn();

        $cpt_participants = 0;


        // Affichage des information :
        echo("Nombre d'archers : ".count($result)."\n");
        echo("</br>\n");
        echo("Taille du cut : ".$taille_cut."\n");
        echo("</br>\n");
        echo("Nombre de préinscrit : ".$nb_preinscrit."\n");
        echo("</br>\n");
        echo("Nombre d'inscription validé : ".$nb_inscrit."\n");
        echo("</br>\n");
        echo("</br>\n");

        // Affichage de la table

        if( $div ) echo("<div class='cutTable divTable' >\n");
        else echo("<table class='cutTable' >\n");
        

        $first_row = true;
        foreach ($result as $row) {
            // Affiche le menu de la table
            if ($first_row) {
                $first_row = false;

                // Start Row
                if( $div ) echo("<div class='divTableRow divTableHeading' >\n");
                else echo "<tr class='tableHeading' >\n";

                // RANK
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Rang";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";

                // NO_LICENCE
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Licence";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                // NOM_PERSONNE
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Nom";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                // PRENOM_PERSONNE
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Prénom";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                // CLUB
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Club";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                // Score 1
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Score 1";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                // Score 2
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Score 2";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                // Score 3
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Score 3";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                // SCORE_TOTAL
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo "Score Total";
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
                
                if ($inscription) {
                    // ETAT
                    if( $div ) echo("<div class='divTableCell' >");
                    else echo "<td>";
                    echo "Etat Inscription";
                    if( $div ) echo("</div>\n");// divTableCell
                    else echo "</td>\n";
                }

                if( $admin ){
                    // UPDATE
                    if( $div ) echo("<div class='divTableCell' >");
                    else echo "<td>";
                    echo "Mise à jour";
                    if( $div ) echo("</div>\n");// divTableCell
                    else echo "</td>\n";
                } else if ($inscription) {
                    // UPDATE
                    if( $div ) echo("<div class='divTableCell' >");
                    else echo "<td>";
                    echo "Pré inscription";
                    if( $div ) echo("</div>\n");// divTableCell
                    else echo "</td>\n";
                }


                // End Row
                if( $div ) echo("</div>\n"); //  divTableRow divTableHeading
                else echo "</tr>\n";
                
                if( $div ) echo("<div class='divTableBody' >\n");
            }
                
            if( $row['ETAT'] == 1)
                $classe_status = "cutPreInscrit";
            elseif( $row['ETAT'] == 2 || $row['ETAT'] == 4)
                $classe_status = "cutInscrit";
            elseif( $row['ETAT'] == 3 )
                $classe_status = "cutRefu";
            else {
                if ( $cpt_participants < $taille_cut )
                    $classe_status = "cutPotentiel";
                else 
                    $classe_status = "cutHorsCut";
            }

            // Start Row
            if( $div ) echo("<div class='divTableRow tableContent $classe_status' >\n");
            else echo "<tr class='tableContent  $classe_status' >\n";

            // RANK
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            echo Printer::row_to_string('RANK', $row['RANK']);
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";

            // NO_LICENCE
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            echo Printer::row_to_string('NO_LICENCE', $row['NO_LICENCE']);
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            // NOM_PERSONNE
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            echo Printer::row_to_string('NOM_PERSONNE', $row['NOM_PERSONNE']);
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            // PRENOM_PERSONNE
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            echo Printer::row_to_string('PRENOM_PERSONNE', $row['PRENOM_PERSONNE']);
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            // CLUB
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            echo Printer::row_to_string('CLUB', $row['CLUB']);
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            $score = explode ( ",", $row['SCORES']);
            // Score 1
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            if( isset( $score[0] ))
                echo $score[0];
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            // Score 2
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            if( isset( $score[1] ))
                echo $score[1];
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            // Score 3
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            if( isset( $score[2] ))
                echo $score[2];
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            // SCORE_TOTAL
            if( $div ) echo("<div class='divTableCell' >");
            else echo "<td>";
            echo Printer::row_to_string('SCORE_TOTAL', $row['SCORE_TOTAL']);
            if( $div ) echo("</div>\n");// divTableCell
            else echo "</td>\n";
            
            if ($inscription) {
                // ETAT
                if( $div ) echo("<div class='divTableCell' >");
                else echo "<td>";
                echo Printer::row_to_string('ETAT', $row['ETAT']);
                if( $div ) echo("</div>\n");// divTableCell
                else echo "</td>\n";
            }

            
            if( $row['ETAT'] != 3 ){
                $cpt_participants++;
            }      

        
            if( $admin ){
                // Start Column
                if( $div ) echo("<div class='divTableHead' >");
                else echo "<th>";

                // Column
                echo "<form name='change_mode' method='post' >";
                echo "<select name='select_mode_archer'>";

                echo "<option value=0 ";
                if( $row['ETAT'] == 0 ) echo "selected='selected' ";
                echo ">Non Inscrit</option>";
                
                echo "<option value=1 ";
                if( $row['ETAT'] == 1 ) echo "selected='selected' ";
                echo ">Pré Inscrit</option>";

                echo "<option value=2 ";
                if( $row['ETAT'] == 2 ) echo "selected='selected' ";
                echo ">Inscrit</option>";

                echo "<option value=3 ";
                if( $row['ETAT'] == 3 ) echo "selected='selected' ";
                echo ">Refus</option>";

                echo "<option value=4 ";
                if( $row['ETAT'] == 4 ) echo "selected='selected' ";
                echo ">Séléction définitive</option>";


                echo "</select>";
                echo "<input type='hidden' name='id' value='".$row['NO_LICENCE']."' >";
                echo "<input type='hidden' name='select_cut' value='".urlencode($cut_name)."' >";
                echo "<input type='hidden' name='".$print_param."' value='".urlencode($cut_name)."' >";
                echo "<input type='submit' value='Valider'/>";
                echo "</form>";

                // End Column
                if( $div ) echo("</div>\n"); // divTableHead
                else echo "</th>\n";
            } else if ($inscription) {
                // Start Column
                if( $div ) echo("<div class='divTableHead' >");
                else echo "<th>";

                if( $row['ETAT'] == 0 ){
					
					echo "<script language='javascript'>\n";
					echo "function submitform".$row['NO_LICENCE']."()\n";
					echo "{\n";
					echo "\t form = document.forms['preinscrire".$row['NO_LICENCE']."'];\n";
					echo "\t date_naissance = prompt('Pour vérifier votre identité, veuillez saisir votre date de naissance (JJ/MM/AAAA)');\n";
					
					echo "\t if( date_naissance ){\n";
					echo "\t\t input = document.createElement('input');\n";
					echo "\t\t input.type = 'hidden';\n";
					echo "\t\t input.name = 'date_naissance';\n";
					echo "\t\t input.value = encodeURI(date_naissance);\n";
					echo "\t\t form.appendChild(input);\n";
					
					echo "\t\t return true;\n";
					echo "\t } else return false;\n";
					echo "}\n";
					echo "</script>\n";
					
					
                    echo "<form name='preinscrire".$row['NO_LICENCE']."' method='post' onsubmit='return submitform".$row['NO_LICENCE']."()'>";
                    echo "<input type='hidden' name='preinscrire' value='true' >";
                    echo "<input type='hidden' name='id' value='".$row['NO_LICENCE']."' >";
                    echo "<input type='hidden' name='select_cut' value='".urlencode($cut_name)."' >";
                    echo "<input type='hidden' name='".$print_param."' value='".urlencode($cut_name)."' >";
                    echo "<input type='submit' value='Pré inscrire' type=button />";
                    echo "</form>";
                }

                // End Column
                if( $div ) echo("</div>\n"); // divTableHead
                else echo "</th>\n";
            }

            // End Row
            if( $div ) echo("</div>\n");// divTableRow
            else echo "</tr>\n";

        }
        
        if( $div ) echo("</div>\n"); //divTable
        else echo("</table>\n");

        if($export){
            $this->_export->print_export_icons( $cut_name );
        }
    }

    public function update_status( ){
        if( isset($_REQUEST['preinscrire'])){
            $id = $_REQUEST['id'];
            $cut_name = urldecode($_REQUEST['select_cut']);

			$date_naissance=$_REQUEST['date_naissance'];
			
			$this->_request->login();
			
			if($this->_request->check_date_naissance($id, $date_naissance)){
			
				$pdo = $this->_bdd->get_PDO();
				$table_name = $this->_bdd->get_table_cut_name($cut_name);

				$sth_update = $pdo->prepare("UPDATE $table_name SET ETAT=:ETAT WHERE NO_LICENCE=:NO_LICENCE");
				
				$sth_update->bindValue(":NO_LICENCE", $id);
				$sth_update->bindValue(":ETAT", "1");

				try{
					$sth_update->execute();

					$row = $pdo->query("SELECT NOM_PERSONNE, PRENOM_PERSONNE, CLUB FROM $table_name WHERE NO_LICENCE='".$id."'")->fetch();
					$this->_logger->log_operation(0, 1, "User : Préinscription de l'archer - ".$id." - ".$row['PRENOM_PERSONNE']." ".$row['NOM_PERSONNE']." - ".$row['CLUB']);

				}catch (\PDOException $e){
					echo "Echec de l'a mise a jour du nouvel état de ".$id." dans ".$cut_name." : ".$e."<br/>\n";
				}

				$email = $this->_request->get_email( $id );
				
				$message = "Bonjour,\n";
				$message .= "\n";
				$message .= "Nous vons informons que votre demande de Pré-Inscription au Championnat Régional d'Occitanie à bien été prise en compte.\n";
				$message .= "\n";
				$message .= "Nom : ".$row['NOM_PERSONNE']."\n";
				$message .= "Prénom : ".$row['PRENOM_PERSONNE']."\n";
				$message .= "Club : ".$row['CLUB']."\n";
				$message .= "Licence : ".$id."\n";
				$message .= "Catégorie : ".$cut_name."\n";
				$message .= "Etat : Pré Inscrit\n";
				$message .= "\n";
				$message .= "Si vous souhaitez vous désinscrire et annuler votre demande de Pré-Inscription au Championnat Régional d'Occitanie, veuillez envoyer un message à lionel.allasio@arc-occitanie.fr \n";
				$message .= "\n";
				$message .= "\n";
				$message .= "Bien Cordialement\n";
				$message .= "La gestion sportive du CRTAO\n";
				$message .= "\n";
							
				Printer::send_mail ( $email , "[CRTAO] Pré-Inscription Championnat Regional CRTAO" ,  $message );
				echo "<br/><br/>Pré Inscription OK - Un email vous a été envoyé à l'adresse $email - Vérifiez dans vos SPAMs<br/><br/>\n";
			} else {
				echo "<br/><br/>La date de naissance saisie n'est pas correcte : $date_naissance <br/><br/>\n";
			}
			
			$this->_request->logout();
			
			
        }
    }
    
    public function update_status_admin( ){
        if( isset($_REQUEST['select_mode_archer'])){
			
			
            $id = $_REQUEST['id'];
            $cut_name = urldecode($_REQUEST['select_cut']);
            $mode = $_REQUEST['select_mode_archer'];

            $pdo = $this->_bdd->get_PDO();
            $table_name = $this->_bdd->get_table_cut_name($cut_name);

            $sth_update = $pdo->prepare("UPDATE $table_name SET ETAT=:ETAT WHERE NO_LICENCE=:NO_LICENCE");
            
            $sth_update->bindValue(":NO_LICENCE", $id);
            $sth_update->bindValue(":ETAT", $mode);

            try{
                $sth_update->execute();

                $row = $pdo->query("SELECT NOM_PERSONNE, PRENOM_PERSONNE, CLUB FROM $table_name WHERE NO_LICENCE='".$id."'")->fetch();
                $this->_logger->log_operation(0, 1, "Admin : Préinscription de l'archer -".$id." - ".$row['PRENOM_PERSONNE']." ".$row['NOM_PERSONNE']." - ".$row['CLUB']);

				$this->_request->login();
				$email = $this->_request->get_email( $id );
				$this->_request->logout();
				
				$message = "Bonjour,\n";
				$message .= "\n";
				$message .= "Nous vous informons que le gestionnaire sportif du CRTAO à changé l'état de votre inscription :\n";
				$message .= "\n";
				$message .= "Nom : ".$row['NOM_PERSONNE']."\n";
				$message .= "Prénom : ".$row['PRENOM_PERSONNE']."\n";
				$message .= "Club : ".$row['CLUB']."\n";
				$message .= "Licence : ".$id."\n";
				$message .= "Catégorie : ".$cut_name."\n";
				$message .= "\n";
				$message .= "Etat : ".Printer::etat_to_string($mode)."\n";
				$message .= "\n";
                                $message .= "\n";
                                if ($mode == 4) {
					$message .= "Vous avez été sélectionné pour le Championnat Régional Salle du CRTAO\n";
                                        $message .= "\n";
                                        $message .= "Les mandats sont disponibles ici :\n";
                                        $message .= "CR Jeune : http://extranet.ffta.fr/medias/documents_epreuves/ec2a7a18399a4.pdf \n";
                                        $message .= "CR Adulte : http://extranet.ffta.fr/medias/documents_epreuves/ec23e9310c140.pdf \n";
                                        $message .= "\n";
                                        $message .= "Félicitation !\n";
                                }
                                $message .= "\n";
                                $message .= "\n";
				$message .= "Bien Cordialement\n";
				$message .= "La gestion sportive du CRTAO\n";
				$message .= "\n";
							
				Printer::send_mail ( $email , "[CRTAO] Inscription Championnat Régional CRTAO" ,  $message );	
				
				echo "<br/><br/>Un mail à été envoyé à $email pour le notifier du changement d'état de son inscription<br/><br/>\n";
				
            }catch (\PDOException $e){
                echo "Echec de l'a mise a jour du nouvel état de ".$id." dans ".$cut_name." : ".$e."<br/>\n";
            }
        }
    }
    
    public static function etat_to_string( $valeur ){
        switch($valeur){
            case 0:
                return "";
            case 1:
                return "PRE-INSCRIT";
            case 2:
                return "INSCRIT";
            case 3:
                return "REFU";
            case 4:
                return "SELECTION_DEFINITIVE";
        }
    }

    public static function row_to_string( $cles, $valeur ){
        if( $cles == "SCORE_TOTAL" ){
            return  strval( ceil( $valeur ) );
        } elseif ( $cles == "ETAT" ){
            return Printer::etat_to_string($valeur);
        } else {
            return htmlspecialchars($valeur);
        } 
    }
	
	public static function send_mail($email, $obj, $message){
						
		//=====Création du header de l'e-mail
		$header = "From: \"Site Arc Occitanie\"<noreply@arc-occitanie.fr>\n";
		$header .= "Reply-to: \"Contact Arc Occitanie\"<contact@arc-occitanie.fr>\n";
		$header .= "MIME-Version: 1.0\n";
		$header .= "Content-Type: text/plain;charset=UTF-8\n";
		//==========
		
		
		mail ( $email , $obj, $message, $header );
		
	}
	
}

?>
