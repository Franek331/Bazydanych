// Obsługa przełączania między formularzami
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Usunięcie klasy active ze wszystkich przycisków
            tabBtns.forEach(function(b) {
                b.classList.remove('active');
            });
            
            // Dodanie klasy active do klikniętego przycisku
            this.classList.add('active');
            
            // Pokazanie odpowiedniego formularza
            const formId = this.getAttribute('data-form');
            if (formId === 'login-form') {
                loginForm.classList.remove('hide');
                registerForm.classList.add('hide');
            } else {
                loginForm.classList.add('hide');
                registerForm.classList.remove('hide');
            }
        });
    });
    
    // Walidacja formularza rejestracji po stronie klienta
    const registerFormElement = document.getElementById('register-form');
    if (registerFormElement) {
        registerFormElement.addEventListener('submit', function(event) {
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Hasła nie są identyczne!');
            }
            
            // Sprawdzenie siły hasła
            if (password.length < 8) {
                event.preventDefault();
                alert('Hasło powinno mieć co najmniej 8 znaków!');
            }
        });
    }
    
    // Pokazanie komunikatu o sukcesie rejestracji i przełączenie na formularz logowania
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        const loginTabBtn = document.querySelector('[data-form="login-form"]');
        if (loginTabBtn) {
            loginTabBtn.click();
        }
        
        // Ukrycie komunikatu po 5 sekundach
        setTimeout(function() {
            successMessage.style.display = 'none';
        }, 5000);
    }
});