<?php
    //save data for  the pair subject/session in the experiment folder
    //status:   0 == done;
    //         -1 == No post (throw an error)
    require './_mailfunctions.php';


    // The server shell running PHP may be missing environment variable $HOME.
    // Yet, git is relying strongly on this variable, so if missing, it needs
    // to be added manually to the environment.
    if (!getenv("HOME")) {
        putenv("HOME=/home/tqmporg"); //adapt to your server
    }

    try{
        //Limit the domains allowed to access this script for security reasons
        //header('Access-Control-Allow-Origin: https://uottawa.ca1.qualtrics.com');
        header('Access-Control-Allow-Origin: *');
        //Default response
        header('Content-Type: application/json');
//        header('Content-Type: text/html');
        // prevent XSS attacks
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $Output = array(
           "error"   => false,
           "message" => "N/A",
           "output"  => "N/A",
           "status"  => "-1"  
        );

        if(isset($_POST['experiment']) && isset($_POST['subject']) && isset($_POST['session']) && isset($_POST['data'])){
            $Subj = $_POST['subject'];
            $Sess = $_POST['session'];
            $Expe = $_POST['experiment'];
            $Data = $_POST['data'];

            // save file
            $Data = str_replace("&#34;","", $Data);   //remove hidden spaces
            $Data = str_replace("WMWMW",'"', $Data);  //restore "" as delimiters where commas
            $rerun = "";
            if (file_exists("./".$Expe."/".$Expe."-".$Subj."-".$Sess. $rerun . ".csv") ) {
                $rerun = "a";
                while (file_exists("./".$Expe."/".$Expe."-".$Subj."-".$Sess . $rerun.".csv")) {
                    ++$rerun; }
            }
            file_put_contents("./".$Expe."/".$Expe."-".$Subj."-".$Sess. $rerun .".csv", $Data);

            // Replace 'once' with 'done' in the invitations; or decrease nber of sessions
            $h   = fopen("./" . $Expe . "/_invitations.txt","r"); 
            $fle = "";
            while(!feof($h)){
                $b       = fgets($h);
                if (!feof($h)) {
                    $oneline = preg_split("/[\s,]+/", $b, NULL , PREG_SPLIT_NO_EMPTY);
                    if (count($oneline)>=4) {
                        if (($oneline[0] == $Subj) and ($oneline[1] == $Sess )) {
                            $ran     = $oneline[2];
                            $email   = $oneline[3];
                            if ($ran != "inf") {
                                if ($ran == "once") $newran = "done";
                                if (intval($ran) == 1) $newran = "done";
                                if (intval($ran) > 1) $newran = strval(intval($ran)-1);

                                $p0 = strpos($b, $oneline[0]);
                                $p1 = strpos($b, $oneline[1], $p0+strlen($oneline[0]) );
                                $p2 = strpos($b, $oneline[2], $p1+strlen($oneline[1]) );
                                $newb = substr($b, 0, $p2) . $newran . substr($b, $p2+strlen($oneline[2])) ;
                                $b = $newb;
                            }
                        }
                    }
                    $fle = $fle . $b;
                }   
            }
            fclose($h);
            if ($ran != "inf") {
                file_put_contents("./" . $Expe . "/_invitations.txt", $fle);
            }

            // grab owner.txt information
            $h=fopen("./" . $Expe . "/_owner.txt","r");
            $b="#empty";
            while((substr($b,0,1) == "#")){
                $b=fgets($h);
            }
            $ownername = str_replace("\r\n", "", $b);         //owner's name: the machine
            $ownermail = str_replace("\r\n", "", fgets($h));  //owner's email
            $gituser   = str_replace("\r\n", "", fgets($h));
            $gitrepo   = str_replace("\r\n", "", fgets($h));
            fclose($h);

            // GIT-related commands add, commit and push
            //check git presence: version 1.8.3.1 is on the tqmp server
            $cmd="git --version";
            exec($cmd. ' 2>&1', $output, $return_var);
            if ($return_var == 0) {
                unset($output);

                chdir("./".$Expe);
                $cmd= "git clone git@github.com:" . $gituser."/" . $gitrepo .".git";
                exec($cmd . ' 2>&1', $output, $return_var);
                if ($return_var != 0) {
                    file_put_contents("../_333.txt", exec("whoami") ."\n".$output);
                } else{//clone worked
                    chdir("./".$Expe);
                    //var_dump(getcwd());


                    // put data in folder rawdata
                    file_put_contents("rawdata/".$Expe."-".$Subj."-".$Sess. $rerun . ".csv", $Data);
                    // append to subjectsLog.txt:
                    if (file_exists("subjectsLog.txt") ) {
                        $fp = fopen('subjectsLog.txt', 'a');
                        fwrite($fp, $Expe . "\t" . $Subj . "\t" . $Sess . (($rerun!="")?"(".$rerun.")":""). "\t" . date("Y-m-d") . "\t" . date('H:i') . "\t" . "Uploaded" . "\n");
                        fclose($fp);
                    }

                    $cmd="git add subjectsLog.txt";
                    exec($cmd.' 2>&1', $output, $return_var);
                    $cmd="git add rawdata/".$Expe."-".$Subj."-".$Sess.$rerun.".csv";
                    exec($cmd.' 2>&1', $output, $return_var);

                    $cmd = 'git commit --message="BornOpen4jsPsych uploaded SUBJECT='.$Subj.', SESSION='.$Sess. (($rerun !='') ? ', RERUN='.$rerun :'').'"';
                    exec($cmd.' 2>&1', $output, $return_var);
                    //var_dump($output);

                    $cmd="git push";
                    exec($cmd.' 2>&1', $output, $return_var);
                    //var_dump($output);

                    //delete folder: yes
                    chdir("..");
                    system("rm -rf ".escapeshellarg($Expe));
                }
                chdir("..");
            }


            // SEND AN EMAIL TO ME for backup
            $email_subject = "Data file being logged into GitHub";
            $email_body    = "Data for EXPERIMENT=$Expe, SUBJECT=$Subj, SESSION=$Sess".(($rerun!="")? ", RERUN=".$rerun : "")." has been loggued successfully.<br><br>";
            mymail1attachment(
                $email_subject, $ownermail,             // from owner'mail
                $ownername, "editorialoffice@tqmp.org", // to owner'name & email
                "",                                     // cc
                $email_body, 
                "./".$Expe."/".$Expe."-".$Subj."-".$Sess. $rerun.".csv", 
                $Expe."-".$Subj."-".$Sess. $rerun.".csv", "csv"  
            );

            // SEND AN EMAIL TO USER
            $email_subject = "Your data have been logged";
            mymail0attachment(
                $email_subject, $email,                 //participant's email;
                $ownername, "editorialoffice@tqmp.org", //owner's name and fake email
                "",                                     //cc
                $email_body 
            );

     
            // finishing package returned
            $Output["output"] = "File saved..."; 
            $Output["status"] = "0"; 
            http_response_code(200);  

        } else { //all three information are not given
            throw new Exception("POST information unprovided..."); 
        }
    } 
    catch (\Throwable $e) {
        $Output["error"]   = true;
        $Output["message"] = $e->getMessage();
    } 
    finally {
        echo json_encode($Output, JSON_FORCE_OBJECT);
        die();
    }

?>