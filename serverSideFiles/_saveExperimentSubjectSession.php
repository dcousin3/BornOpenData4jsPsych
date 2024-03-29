<?php
    //save data for  the pair subject/session in the experiment folder
    //status:   0 == done;
    //         -1 == No post (throw an error)
    // version 30.06.2023: two changes
    //      a) Added trim() to fgets as encoding of end-of-lines was not consistent...
    //      b) Added a secret file containing the github sshkey. CHANGE TO YOUR SECRET FILE...
    require './_mailfunctions.php';
    $secretfile = "/_xHeCNf.txt";  // the slash for the subdirectory; contains the ssh key
    $eol = "\n"; 

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
            $wildcards = false;
            $foundsubj = false;
            
            while(!feof($h)){
                $b       = trim(fgets($h)) . $eol ;
                $newb    = "";

                if (!feof($h)) {
                    if (! $foundsubj ) {
                        $oneline = preg_split("/[\s,]+/", $b, NULL , PREG_SPLIT_NO_EMPTY);
                        if (count($oneline)>=4) {
                            $subject = $oneline[0];
                            $session = $oneline[1];
                            $ran     = $oneline[2];
                            $email   = $oneline[3];

                            // processing the wild cards
                            if ($subject == '*') { $wildcards = true; $subject = $Subj; };
                            if ( preg_match( "/[%+@+\?+]/", $subject )== 1) {
                                $str1 =  preg_replace("/%/", "\d", $subject);
                                $str1 =  preg_replace("/@/", "[a-zA-Z]", $str1 );
                                $str1 =  preg_replace("/\?/", "[a-zA-Z0-9]", $str1 );
                                if ( preg_match( "/\b".$str1."\b/", $Subj)==1 ) {
                                    $subject = $Subj;
                                    $wildcards = true;
                                }
                            }

                            // if participant found, update its line
                            if (($subject == $Subj) and ($session == $Sess )) {
                                $foundsubj = true;

                                if ($ran == "inf")     $newran = "inf";
                                if ($ran == "once")    $newran = "done";
                                if (intval($ran) == 1) $newran = "done";
                                if (intval($ran) > 1)  $newran = strval(intval($ran)-1);
                                //substitute in b the newran; to keep formatting and comments if any
                                $p0 = strpos($b, $oneline[0]);
                                $p1 = strpos($b, $oneline[1],    $p0+strlen($oneline[0]) );
                                $p2 = strpos($b, $oneline[2],    $p1+strlen($oneline[1]) );

                                $newb = strpos($b,0, $p0) . $subject . substr($b, $p0+strlen($oneline[0]), $p2-$p0-strlen($oneline[0])) . $newran . substr($b, $p2+strlen($ran), -1) . ($wildcards ? "     (matching wildcards ".$oneline[0].")":"") . $eol;

                                // the new line replace the old line
                                if ( ! $wildcards ) { $b = ""; }
                            }


                        } // end of if (count() >= 4)
                    } // end of if not found

                    $fle = $fle . $newb . $b ;

                } // enf of if !eof
            }
            fclose($h);
            file_put_contents("./" . $Expe . "/_invitations.txt", $fle);

 
            // grab owner.txt information
            $h=fopen("./" . $Expe . "/_owner.txt","r");
            $b="#empty";
            while((substr($b,0,1) == "#")){
                $b=trim(fgets($h));
            }
            $ownername = str_replace("\r\n", "", $b);         //owner's name: the machine
            $ownermail = str_replace("\r\n", "", trim(fgets($h)) );  //owner's email
            $gituser   = str_replace("\r\n", "", trim(fgets($h)) );
            $gitrepo   = str_replace("\r\n", "", trim(fgets($h)) );
            fclose($h);
    
            // grab ssh secret passkey; change the file name to preserve privacy.
            $h=fopen("./" . $Expe . $secretfile, "r");
            $ssh=trim(fgets($h));
            fclose($h);


            // GIT-related commands add, commit and push
            //check git presence: version 1.8.3.1 is on the server
            $cmd="git --version";
            exec($cmd. ' 2>&1', $output, $return_var);
            if ($return_var == 0) {
                unset($output);

                chdir("./".$Expe);
                $cmd= "git clone https://ghp_".$ssh."@github.com/" . $gituser."/" . $gitrepo .".git";
                exec($cmd . ' 2>&1', $output, $return_var);
                if ($return_var != 0) {
                    file_put_contents("../_ERROR-ON-CLONE.txt", exec("whoami") ."\n".$output);
                } else {//clone worked
                    chdir("./".$Expe);
                    //var_dump(getcwd());

                    // put data in folder rawdata
                    file_put_contents("./rawdata/".$Expe."-".$Subj."-".$Sess. $rerun . ".csv", $Data);
                    // append to subjectsLog.txt:
                    if (file_exists("subjectsLog.txt") ) {
                        $fp = fopen('subjectsLog.txt', 'a');
                        fwrite($fp, $Expe . "\t" . $Subj . "\t" . $Sess . (($rerun!="")?"(".$rerun.")":""). "\t" . date("Y-m-d") . "\t" . date('H:i') . "\t" . "Uploaded" . "\n");
                        fclose($fp);
                    }

                    $cmd="git add subjectsLog.txt";
                    exec($cmd.' 2>&1', $output, $return_var);
                    $cmd="git add ./rawdata/".$Expe."-".$Subj."-".$Sess.$rerun.".csv";
                    exec($cmd.' 2>&1', $output, $return_var);

                    $cmd = 'git commit --message="BornOpen4jsPsych uploaded SUBJECT='.$Subj.', SESSION='.$Sess. (($rerun !='') ? ', RERUN='.$rerun :'').'"';
                    exec($cmd.' 2>&1', $output, $return_var);
                    //var_dump($output);

                    // $cmd="git push";
                    $cmd="git push https://ghp_".$ssh."@github.com/" .$gituser."/". $gitrepo. ".git";
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
                "./".$Expe."/".$Expe."-".$Subj."-".$Sess.$rerun.".csv", 
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