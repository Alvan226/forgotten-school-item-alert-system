
document.addEventListener("DOMContentLoaded", () => {

    // 1. Show and hide passwords
    document.querySelectorAll("[data-toggle-password]").forEach((button) => {
        button.addEventListener("click", () => {
            const input = document.querySelector(
                button.dataset.togglePassword
            );

            if (!input) return;

            if (input.type === "password") {
                input.type = "text";
                button.textContent = "Hide";
            } else {
                input.type = "password";
                button.textContent = "Show";
            }
        });
    });


    // 2. Check password confirmation
    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", (event) => {

            const password = form.querySelector(
                'input[name="password"]'
            );

            const confirmation = form.querySelector(
                'input[name="confirm_password"]'
            );

            if (password && confirmation) {

                if (password.value !== confirmation.value) {
                    event.preventDefault();

                    alert("Your passwords do not match.");

                    confirmation.focus();
                    return;
                }
            }

        });
    });


    // 3. Confirm before deleting records
    document.querySelectorAll("form[data-confirm]").forEach((form) => {

        form.addEventListener("submit", (event) => {

            const message = form.dataset.confirm ||
                "Are you sure you want to continue?";

            if (!confirm(message)) {
                event.preventDefault();
            }

        });

    });


    // 4. Prevent accidental double submissions
    document.querySelectorAll("form").forEach((form) => {

        form.addEventListener("submit", () => {

            if (!form.checkValidity()) return;

            const submitButton = form.querySelector(
                'button[type="submit"], input[type="submit"]'
            );

            if (submitButton) {
                submitButton.dataset.originalText =
                    submitButton.textContent;

                submitButton.disabled = true;
                submitButton.textContent = "Processing...";
            }

        });

    });


    // 5. Interactive checklist progress
    const checklistItems = document.querySelectorAll(
        '[data-checklist-item]'
    );

    const progressText = document.querySelector(
        '[data-progress-text]'
    );

    const progressBar = document.querySelector(
        '[data-progress-bar]'
    );

    function updateChecklistProgress() {

        if (!checklistItems.length) return;

        const total = checklistItems.length;

        const completed = Array.from(checklistItems).filter(
            (item) => item.checked
        ).length;

        const percentage = Math.round(
            (completed / total) * 100
        );

        if (progressText) {
            progressText.textContent =
                `${completed} of ${total} items packed (${percentage}%)`;
        }

        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute(
                "aria-valuenow",
                percentage
            );
        }
    }

    checklistItems.forEach((item) => {
        item.addEventListener("change", updateChecklistProgress);
    });

    updateChecklistProgress();


    // 6. Smooth scrolling for page navigation
    document.querySelectorAll('a[href^="#"]').forEach((link) => {

        link.addEventListener("click", (event) => {

            const target = document.querySelector(
                link.getAttribute("href")
            );

            if (target) {
                event.preventDefault();

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }

        });

    });

});