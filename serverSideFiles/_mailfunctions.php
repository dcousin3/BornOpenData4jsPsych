<?php
    /* Functions to send emails */
    function mymail0attachment($subject, $to, $name, $from, $cc, $message) {
        // carriage return type (RFC)
        $eol = "\r\n";   

        $headers  = "From: {$name} <{$from}>" . $eol;
        $headers .= "Cc: {$cc}" . $eol;
        $headers .= "Reply-To: {$from}" . $eol;
        $headers .= "Bcc: editorialoffice@tqmp.org" .$eol;
        $headers .= "MIME-Version: 1.0" . $eol;
        $headers .= "Content-type: text/html; charset=UTF-8" . $eol;

        return( mail($to, $subject, $message, $headers, "-f{$from}" ) );
    }

    function mymail1attachment($subject, $to, $name, $from, $cc, $message, $file, $filename, $filetype) {
        $content = file_get_contents($file);
        $content = chunk_split(base64_encode($content));
 
        $randomVal = md5(time()); 
        $mimeBoundary = "Multipart_Boundary_{$randomVal}_"; 

        // carriage return type (RFC)
        $eol = "\r\n"; 
        
       // main header (multipart mandatory)
        $headers  = "From: {$name}<{$from}>" . $eol;
        $headers .= "Cc: {$cc}" . $eol;
        $headers .= "Reply-To: {$from}" . $eol;
        $headers .= "Bcc: editorialoffice@tqmp.org " . $eol;
        $headers .= "MIME-Version: 1.0" . $eol;
        $headers .= "Content-Type: multipart/mixed; " . $eol;
        $headers .= " boundary=\"{$mimeBoundary}\"" . $eol . $eol;

        // message
        $body  = "This is a multi-part message in MIME format." . $eol;
        $body .= "--{$mimeBoundary}" . $eol;
        $body .= "Content-Type: text/html; charset=UTF-8" . $eol;
        $body .= "Content-Transfer-Encoding: 7bit" . $eol . $eol; //here two mandatory \n
        $body .= $message . $eol;

        // attachment
        $body .= "--{$mimeBoundary}" . $eol;
        $body .= "Content-Type: application/octet-stream; name=\"{$filename}\"" . $eol;
        $body .= "Content-Transfer-Encoding: base64" . $eol . $eol;
//        $body .= "Content-Disposition: attachment; filename=\"{$filename}\";" . $eol . $eol; //here two mandatory \n
        $body .= $content . $eol;
        $body .= "--{$mimeBoundary}--" .$eol;

        //SEND Mail
        return( mail($to, $subject, $body, $headers, "-f{$from}" ) );
    }

?>
