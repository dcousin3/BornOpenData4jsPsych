# BornOpenData4jsPsych

Proved Born-Open capabilities to jsPsych through the use of a simple plugin and a function.

It contains 
- ServerSideFiles: The PHP scripts that are to be installed on the server. This script verifies the invitations and 
    when data are sent, keeps a copy locally on the server and git add them to a GitHub server account found in the owner file.
- jsPsychFiles: The plugin for jsPsych version 6
- jsPsychFiles7: The plugin for jsPsych version 7.

The zip file testExperiment.zip contains the full script for a BornOpen experiment. Place it on any server.

## 2023 update

It is now possible to have wildcards in the invitation file. For example, participants ??? could be allowed to run the experiment, where ? matches any characters. Other wildcards are @  (any letter), % (any digit) and ? (any letter or digit).

## How to cite

See Denis Cousineau (2021) Born-Open Data for jsPsych. PsyArXiv. https://doi.org/10.31234/osf.io/rkhng

Follow [this link](https://psyarxiv.com/rkhng) for the text.

## You see a bug?

This is a beta version. Open an issue here or contact me for comments/bug reports.

## Note

This text is not meant to be published in a journal. The only version is the present psyarXiv article.
