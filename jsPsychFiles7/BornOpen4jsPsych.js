/**
 * jspsych-BornOpen4jsPsych-Initialize
 * Denis Cousineau, 2023 (update for jsPsych v7)
 *
 * plugin for preparing BornOpen automatic save of data file
 *
 * documentation: Cousineau, D. (2021) Born-Open Data for jsPsych. PsyArXiv.
 *      doi: 10.31234/osf.io/rkhng
 *
 **/

var jsPsychBornOpen4jsPsychInitialize = (function(jspsych){
    "use strict";

    const info = {
        name: 'BornOpen4jsPsychInitialize',
        parameters: {
            experiment: {
                type: jspsych.ParameterType.STRING,
                pretty_name: 'Experiment',
                default: undefined,
                description: 'Experiment name'
            },
            subject: {
                type: jspsych.ParameterType.STRING,
                pretty_name: 'Subject',
                default: null,
                description: 'Subject identifier that is measured'
            },
            session: {
                type: jspsych.ParameterType.STRING,
                pretty_name: 'Session',
                default: null,
                description: 'Session identifier to be run'
            },
            server: {
                type: jspsych.ParameterType.STRING,
                pretty_name: 'Server',
                default: undefined,
                description: 'The data server in charge of receiving and versioning the data'
            },
            texts: {
                type: jspsych.ParameterType.HTML_STRING,
                array: true,
                pretty_name: 'Array of displayed texts from top to bottom',
                default: [ "Identification",
                           "Enter your subject Id:",
                           "Enter you session number:",
                           "Validate",
                           "Begin the experiment!"]
            }
        }
    };

        
    class BornOpen4jsPsychInitializePlugin {
                
        constructor(jsPsych) {
            this.jsPsych = jsPsych;
        }

        trial(display_element, trial) {
            var topline, botline;
            
            //if no server defined, make this crash
            if (trial.server===undefined) {alert("Undefined server in BornOpen4jsPsych..."); crash;}

            BornOpen4jsPsychServer = trial.server;

            if ((trial.subject)&&(trial.session)) {
                // if both Subject and Session are defined, skip form
                checkExperimentSubjectSession(trial.experiment, trial.subject, trial.session)
                // after a while, check the result; go straigth to next event if ok
                function ifdone() {
                    if (BornOpen4jsPsychStatus == 0) {
                        this.jsPsych.finishTrial()
                    } else {
                        setTimeout( ifdone, 500);
                    }
                }
                setTimeout( ifdone, 500);

            } else {
                // if one or both are not null, shows the form
                if (!trial.subject) topline = ` 
                        <label for="BornOpen4jsPsych.subject" class="BornOpen4jsPsychformlabel">${trial.texts[1]}</label>
                        <input type="text" id="BornOpen4jsPsych.subject" name="subject" class="BornOpen4jsPsychforminput">
                        <br>
                `; else topline = `<input type="text" hidden Id="BornOpen4jsPsych.subject" name="subject" value="${trial.subject}">`;

                if (!trial.session) botline = `
                        <label for="BornOpen4jsPsych.session" class="BornOpen4jsPsychformlabel">${trial.texts[2]}</label>
                        <input type="text" id="BornOpen4jsPsych.session" name="session" class="BornOpen4jsPsychforminput">
                        <br>
                `; else botline = `<input type="text" hidden Id="BornOpen4jsPsych.session" name="session" value="${trial.session}">`;

                var form = `
                <form Id="check" onsubmit="return false;">
                    <fieldset class="BornOpen4jsPsychfieldset">
                        <legend class="BornOpen4jsPsychformlegend">${trial.texts[0]}</legend>
                        <input type="text" hidden Id="BornOpen4jsPsych.experiment" value="${trial.experiment}">
                        ${topline}
                        ${botline}
                        <label for="go" class="BornOpen4jsPsychformlabel"></label><button id="go" 
                            onclick = "setCall();" 
                            class="BornOpen4jsPsychformbutton">${trial.texts[3]}</button>
                        <label for="go" class="BornOpen4jsPsychformlabel"></label>
                        <p Id="BornOpen4jsPsych.response" class="BornOpen4jsPsychforminput">No response yet</p>
                    </fieldset>
                </form>
                <button disabled Id="continue" class="BornOpen4jsPsychformbutton" >${trial.texts[4]}</button>
                `;  //this.jsPsych.finishTrial()
                var new_html = '<div id="BornOpen4jsPsychInitialize">'+form+'</div>';
                // draw
                display_element.innerHTML = new_html;
                
                // add event listeners to button  
                document.getElementById("continue").addEventListener("click", 
                    (e) => {this.jsPsych.finishTrial(); });

            }
        };

    };

    BornOpen4jsPsychInitializePlugin.info = info;

    return BornOpen4jsPsychInitializePlugin;
})(jsPsychModule);



    /////////////////////////////////////////////////////////////////////////////////////
    // global variables    
    /////////////////////////////////////////////////////////////////////////////////////
    var BornOpen4jsPsychExperiment;
    var BornOpen4jsPsychSubject;
    var BornOpen4jsPsychSession;
    var BornOpen4jsPsychServer;
    var BornOpen4jsPsychStatus;
    var BornOpen4jsPsychOutput;

    /////////////////////////////////////////////////////////////////////////////////////
    // the server functions
    /////////////////////////////////////////////////////////////////////////////////////
    // a proxy for the full check function used in the identification form
    function setCall() {
        document.getElementById("continue").disabled=true;
        checkExperimentSubjectSession(
            document.getElementById("BornOpen4jsPsych.experiment").value.trim(),
            document.getElementById("BornOpen4jsPsych.subject").value.trim(),
            document.getElementById("BornOpen4jsPsych.session").value.trim()
        )

        // after a while, check the result; turn on the button if ok
        function ifresult() {
            if (BornOpen4jsPsychStatus == 0) {
                document.getElementById("continue").disabled=false;
            } else {
                setTimeout( ifresult, 500);
            }
        }
        setTimeout( ifresult, 500);

    }


    // the full check function to be used autonomously
    function checkExperimentSubjectSession(experiment, subject, session){
        try{
            let DataSent = new FormData();
                DataSent.append("experiment", experiment.trim() );
                DataSent.append("subject",    subject.trim() );
                DataSent.append("session",    session.trim() );

            let request = jQuery.ajax({
                type:        "POST", 
                url:         BornOpen4jsPsychServer + "/_checkExperimentSubjectSession.php",
                data:        DataSent,
                dataType:    'json',
                timeout:     120000, //2 Minutes
                cache:       false,
                contentType: false,
                processData: false,
                beforeSend:  function () {
                  //Code à jouer avant l'appel ajax en lui même
                }
            });
            request.done(function (output_success) {
                //Code à jouer en cas d'éxécution sans erreur du script du PHP
                if (document.getElementById("BornOpen4jsPsych.response"))
                    document.getElementById("BornOpen4jsPsych.response").innerHTML = output_success.output;  
                // everything is validated; lets save this information
                BornOpen4jsPsychExperiment = experiment.trim();
                BornOpen4jsPsychSubject    = subject.trim();
                BornOpen4jsPsychSession    = session.trim();
                BornOpen4jsPsychStatus     = output_success.status;
                BornOpen4jsPsychOutput     = output_success.output;
            });
            request.fail(function (http_error) {
                //Code à jouer en cas d'erreur du script du PHP
                let code = http_error.status;
                let server_msg = http_error.responseText;
                let code_label = http_error.statusText;
                if (document.getElementById("BornOpen4jsPsych.showerror"))
                    document.getElementById("BornOpen4jsPsych.showerror").innerHTML = "Erreur "+code+" ("+code_label+") : "  + server_msg;
            });
            request.always(function () {
                 //Code à jouer après done OU fail dans tous les cas 
            });
        }
        //if anything else goes wrong:
        catch(e){
            alert("BornOpen4jsPsych: " + e);
        }
    }


    function convertJSONtext2CSVtext(data) {
        const items = JSON.parse(data);
        function myreplacer(key, value){
            if (typeof value === "string") {
                value = value.replace("\n","");
                if (value.includes(",")) value = 'WMWMW' + value + 'WMWMW'; //will become quotes on the server
            }
            return value;
        }
        const header = Object.keys(items[0])
        const csv = [
          header.join(','), // header row first
          ...items.map(row => header.map(fieldName => JSON.stringify(row[fieldName], myreplacer)).join(','))
        ].join('\r\n')
        return csv;
    }

    // a proxy for the full save function
    function BornOpen4jsPsychSave(data) {
        saveExperimentSubjectSession(
            BornOpen4jsPsychExperiment,
            BornOpen4jsPsychSubject,
            BornOpen4jsPsychSession,
            convertJSONtext2CSVtext(data)
        )
    }

    // the full save function 
    function saveExperimentSubjectSession(experiment, subject, session, data){
        try{
            let DataSent = new FormData();
                DataSent.append("experiment", experiment );
                DataSent.append("subject",    subject );
                DataSent.append("session",    session );
                DataSent.append("data",       data )

            let request =
                jQuery.ajax({
                    type:        "POST", 
                    url:         BornOpen4jsPsychServer + "/_saveExperimentSubjectSession.php",
                    data:        DataSent,
                    dataType:    'json',
                    timeout:     120000, //2 Minutes
                    cache:       false,
                    contentType: false,
                    processData: false,
                    beforeSend:  function () {}
                });
                request.done(function (output_success) {
                    //Code à jouer en cas d'éxécution sans erreur du script du PHP
                });
                request.fail(function (http_error) {
                    //Code à jouer en cas d'erreur du script du PHP
                    let code = http_error.status;
                    let server_msg = http_error.responseText;
                    let code_label = http_error.statusText;
                    alert("BornOpen4jsPsych is unable to save data...")
                });
                request.always(function () {
                     //Code à jouer après done OU fail dans tous les cas 
                });
        }
        //if anything else goes wrong:
        catch(e){
            alert(e);
        }
    };



