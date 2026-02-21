<?php
require_once 'db.php';

foreach ($_POST['stages'] as $stage_num => $stage_data) {

    $correctAnswer = null;
    $falseAnswers = [];

    foreach ($stage_data['answers'] as $answer) {

        $text = $answer['text'];
        $isCorrect = $answer['is_correct']; //checks hidden input value of answersHTML div. 

        if ($isCorrect == 1) {
            $correctAnswer = $text; //assigns $text of answer to one of two things: a $correctAns variable, or an array of the false answers.
        } else {
            $falseAnswers[] = $text;
            }
        }
    }
?>