console.log("module.js loaded"); //debugging. checking if this is loading


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

            const nextStageNum = parseInt(stageNum) + 1
            const nextStage = document.getElementById(`stage_${nextStageNum}`);
            const isFinalStage = !nextStage; //checking if there is a 'next stage';

            // sends to backend for validation

            fetch('check_answer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        answer_id: answerId,
                        stage_num: stageNum,
                        is_final_stage: isFinalStage,
                        module_id: moduleId
                    })
            })

            
            
            .then(res => res.json())
            
            .then(data => {
                console.log("Server response:", data);
                if (data.correct) {

                    if (data.completed) {
                        alert("Module complete!");
                    } else {
                        nextStage.classList.remove('hidden');
                    }

                } else {
                    alert("Incorrect answer, try again.");
                }

            })
            .catch(err => console.error(err));

        });

    });
        // completion for modules without quizzes
        const completeBtn = document.getElementById("completeModuleBtn");

        if (completeBtn) {

            completeBtn.addEventListener("click", () => {

                fetch("check_answer.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        module_id: moduleId,
                        no_quiz: true
                    })
                })
                .then(res => res.json())
                .then(data => {

                    if (data.completed) {
                        alert("Module complete!");
                        completeBtn.disabled = true;
                    }

                })
                .catch(err => console.error(err));

            });

        }
});