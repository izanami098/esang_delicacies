const progress = document.getElementById('progress');
const nextBtn = document.getElementById('next');
const progressSteps = document.querySelectorAll('.progress-step');

let currentActive = 0;

function updateProgressBar() {
    progressSteps.forEach((step, idx) => {
        if (idx <= currentActive) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });

    const activeSteps = document.querySelectorAll('.progress-step.active');
    const progressWidth = ((activeSteps.length - 1) / (progressSteps.length - 1)) * 100;
    progress.style.width = progressWidth + '%';

    // Disable "Next" button if at the last step
    if (currentActive === progressSteps.length - 1) {
        nextBtn.disabled = true;
    } else {
        nextBtn.disabled = false;
    }
}

nextBtn.addEventListener('click', () => {
    currentActive++;
    if (currentActive > progressSteps.length - 1) {
        currentActive = progressSteps.length - 1;
    }
    updateProgressBar();
});

// Initial update to set the first step as active
updateProgressBar();