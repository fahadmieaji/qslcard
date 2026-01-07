document.addEventListener('DOMContentLoaded', function() {
    const steps = document.querySelectorAll('.install-step');
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    const installForm = document.getElementById('install-form');
    const progressBar = document.getElementById('progress-bar');
    const logOutput = document.getElementById('log-output');
    let currentStep = 0;

    function showStep(stepIndex) {
        steps.forEach((step, index) => {
            step.style.display = 'none';
        });
        steps[stepIndex].style.display = 'block';
        currentStep = stepIndex;
    }

    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep < steps.length - 1) {
                showStep(currentStep + 1);
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep > 0) {
                showStep(currentStep - 1);
            }
        });
    });

    installForm.addEventListener('submit', function(e) {
        e.preventDefault();
        showStep(2); // Show progress step

        const formData = new FormData(installForm);
        formData.append('action', 'install');

        fetch('install.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            logOutput.innerHTML += data.message + '<br>';
            progressBar.style.width = data.progress + '%';
            progressBar.innerText = data.progress + '%';

            if (data.status === 'success' && data.progress === 100) {
                setTimeout(() => {
                    showStep(3); // Show congratulations step
                    let count = 10;
                    const countdown = document.getElementById('countdown');
                    const interval = setInterval(() => {
                        count--;
                        countdown.innerText = count;
                        if (count === 0) {
                            clearInterval(interval);
                            window.location.href = data.root_url || 'index.php';
                        }
                    }, 1000);
                }, 1000);
            } else if (data.status === 'error') {
                logOutput.innerHTML += '<span class="text-danger">Error: ' + data.error + '</span><br>';
            } else {
                // Continue installation
                continueInstallation(data.next_step, formData);
            }
        })
        .catch(error => {
            logOutput.innerHTML += '<span class="text-danger">An unexpected error occurred.</span><br>';
            console.error('Error:', error);
        });
    });
    
    function continueInstallation(nextStep, formData) {
        formData.set('action', nextStep);
        
        fetch('install.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            logOutput.innerHTML += data.message + '<br>';
            progressBar.style.width = data.progress + '%';
            progressBar.innerText = data.progress + '%';

            if (data.status === 'success' && data.progress === 100) {
                setTimeout(() => {
                    showStep(3); // Show congratulations step
                    let count = 10;
                    const countdown = document.getElementById('countdown');
                    const interval = setInterval(() => {
                        count--;
                        countdown.innerText = count;
                        if (count === 0) {
                            clearInterval(interval);
                            window.location.href = data.root_url || 'index.php';
                        }
                    }, 1000);
                }, 1000);
            } else if (data.status === 'error') {
                logOutput.innerHTML += '<span class="text-danger">Error: ' + data.error + '</span><br>';
            } else {
                // Continue installation
                continueInstallation(data.next_step, formData);
            }
        })
        .catch(error => {
            logOutput.innerHTML += '<span class="text-danger">An unexpected error occurred.</span><br>';
            console.error('Error:', error);
        });
    }

    showStep(0);
});
