
const menuButton = document.getElementById('menu-button');
const navLinks = document.querySelector('.nav-links');

function toggleMenu() {
    navLinks.classList.toggle('open');
    
    // Accessibility & Icon Change
    const isExpanded = navLinks.classList.contains('open');
    menuButton.setAttribute('aria-expanded', isExpanded);
    menuButton.innerHTML = isExpanded ? '✕' : '☰';
}

if(menuButton) {
    menuButton.addEventListener('click', toggleMenu);
}

// Close menu when a link is clicked
navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
        if (navLinks.classList.contains('open')) {
            toggleMenu();
        }
    });
});

/* Form Validation */
const contactForm = document.getElementById('contact-form-id');
const messageDiv = document.getElementById('form-message');

if (contactForm && messageDiv) {
    contactForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const nameInput = document.getElementById('name').value.trim();
        const emailInput = document.getElementById('email').value.trim();

        if (nameInput === '' || emailInput === '') {
            messageDiv.textContent = '⚠ Please fill out all required fields.';
            messageDiv.style.color = '#d9534f'; // Red
        } else {
            // Success Simulation
            messageDiv.textContent = `Thanks, ${nameInput}! Message sent successfully.`;
            messageDiv.style.color = '#5cb85c'; // Green
            contactForm.reset();
        }
    });
}
