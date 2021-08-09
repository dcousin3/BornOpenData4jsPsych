<?php
    // verify that the pair subject/session is in the invitation list of the experiment
    //status:   0 == yes;
    //          1 == unknown experiment; 
    //          2 == unknown subject/sess pair; 
    //          3 == session already done for that subject; 
    //         -1 == No post (throw an error)


    try{
        //Limit the domains allowed to access this script for security reasons
        //header('Access-Control-Allow-Origin: https://uottawa.ca1.qualtrics.com');
        header('Access-Control-Allow-Origin: *');
        //Default response
        header('Content-Type: application/json');
        // prevent XSS attacks
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $Output = array(
           "error"   => false,
           "message" => "N/A",
           "output"  => "N/A",
           "status"  => "-1"  
        );

        if(isset($_POST['experiment']) && isset($_POST['subject']) && isset($_POST['session'])){
            $Expe = $_POST['experiment'];
			$Subj = $_POST['subject'];
			$Sess = $_POST['session'];

			// HERE search the database for this duo
            $oneline = array();
            $subject = "";
            $session = "";

            if (is_dir("./" . $Expe)) {
                $h=fopen("./" . $Expe . "/_invitations.txt","r"); 
                while(!feof($h) and !(($subject == $Subj) and ($session == $Sess ))){
                    $b=fgets($h);
                    if (substr($b,1,1) != "#") { //Skip comments begining with #
                        if (!feof($h)) {
                            $oneline = preg_split("/[\s,]+/", $b, NULL , PREG_SPLIT_NO_EMPTY);
                            $subject = $oneline[0];
                            $session = $oneline[1];
                            $ran     = $oneline[2];
                        }
                    }
                }
                if (feof($h)) {
                    $Response = "Subject/Session unknown...";
                    $Status   = 2;
                } elseif ($ran == "done" ) {
                    $Response = "Subject/Session already performed...";
                    $Status   = 3;
                } else {
                    $Response = "Subject/Session ok!";
                    $Status   = 0;
                }
                fclose($h);
            } else {
                $Response = "Experiment unknown...";
                $Status   = 1;
            }
            $Output["output"] = $Response; 
            $Output["status"] = $Status; 
            http_response_code(200);  
        } else { //all three information are not given
            throw new Exception("POST information unprovided..."); 
        }
    } 
    catch (\Throwable $e) {
        $Output["error"] = true;
        $Output["message"] = $e->getMessage();
    } 
    finally {
        echo json_encode($Output, JSON_FORCE_OBJECT);
        die();
    }

?>