document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.submit-stage').forEach(button => { //selects each button with class .submit-stage, adds event listener function on click.
        button.addEventListener('click', function() {
            console.log('Submit button clicked for stage:', this.dataset.stage); //  debug
            const stageNum = this.dataset.stage; //refers to the custom data-stage attribute in the stage submit button

            // get the selected answer for stage
            const questionRadios = document.querySelectorAll(`#stage_${stageNum} input[type="radio"]:checked`); //:checked adds constraint to pick value that is actually chosen
            if (questionRadios.length === 0) { //checks to make sure an answer is picked.
                alert("Please select an answer.");
                return;
            }

            
            const answerId = questionRadios[0].value;

            // sends to backend for validation
            fetch('check_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    answer_id: answerId,
                    stage_num: stageNum
                })

            })
            .then(res => res.json())
            .then(data => {
                if (data.correct) {  // advance to next stage once question is correct
                    const nextStage = document.getElementById(`stage_${parseInt(stageNum) + 1}`);

                    if (nextStage) {
                        nextStage.classList.remove('hidden');
                    }

                } else {
                    alert("Incorrect answer, try again.");
                }
            })
            .catch(err => console.error(err));
        });
    });
});